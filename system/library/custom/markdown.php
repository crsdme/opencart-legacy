<?php

namespace Custom;

class Markdown
{
	private $slugs = [];

	public function parse($text, $linkResolver = null)
	{
		$this->slugs = [];
		$text = str_replace(["\r\n", "\r"], "\n", (string) $text);
		$text = str_replace("\t", '    ', $text);
		$text = str_replace(["\xC2\xA0", "\xE2\x80\x94"], [' ', '-'], $text);

		$codes = [];
		$text = preg_replace_callback('/^```([a-zA-Z0-9_-]*)[ \t]*\n(.*?)(?:\n```[ \t]*$|\n```[ \t]*\n)/ms', function (
			$match
		) use (&$codes) {
			$token = '<!--CODE' . count($codes) . '-->';
			$lang = htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8');
			$code = htmlspecialchars(rtrim($match[2], "\n"), ENT_QUOTES, 'UTF-8');
			$codes[$token] =
				'<pre class="docs-pre"><code' .
				($lang !== '' ? ' data-lang="' . $lang . '"' : '') .
				'>' .
				$code .
				'</code></pre>';

			return $token;
		}, $text);

		$blocks = preg_split("/\n{2,}/", trim($text));
		$html = [];

		foreach ($blocks as $block) {
			$block = trim($block);

			if ($block === '') {
				continue;
			}

			if (preg_match('/^<!--CODE\d+-->$/', $block)) {
				$html[] = $block;
				continue;
			}

			if (preg_match('/^---+$/', $block) || preg_match('/^\*\*\*+$/', $block)) {
				$html[] = '<hr />';
				continue;
			}

			if (preg_match('/^# /', $block) || preg_match('/^## /', $block) || preg_match('/^### /', $block) || preg_match('/^#### /', $block)) {
				foreach (preg_split("/\n/", $block) as $line) {
					$line = trim($line);

					if ($line === '') {
						continue;
					}

					if (preg_match('/^(#{1,4})\s+(.+)$/', $line, $match)) {
						$html[] = $this->heading((int) strlen($match[1]), $match[2], $linkResolver);
					} else {
						$html[] = '<p>' . $this->inline($line, $linkResolver) . '</p>';
					}
				}

				continue;
			}

			if ($this->isTable($block)) {
				$html[] = $this->table($block, $linkResolver);
				continue;
			}

			if (preg_match('/^(\s*)([-*] |\d+\. )/', $block)) {
				$html[] = $this->listBlock($block, $linkResolver);
				continue;
			}

			$paragraph = [];

			foreach (preg_split("/\n/", $block) as $line) {
				$line = trim($line);

				if ($line === '') {
					continue;
				}

				if (preg_match('/^(#{1,4})\s+(.+)$/', $line, $match)) {
					if ($paragraph) {
						$html[] = '<p>' . $this->inline(implode(' ', $paragraph), $linkResolver) . '</p>';
						$paragraph = [];
					}

					$html[] = $this->heading((int) strlen($match[1]), $match[2], $linkResolver);
					continue;
				}

				$paragraph[] = $line;
			}

			if ($paragraph) {
				$html[] = '<p>' . $this->inline(implode(' ', $paragraph), $linkResolver) . '</p>';
			}
		}

		$output = implode("\n", $html);

		foreach ($codes as $token => $code) {
			$output = str_replace($token, $code, $output);
		}

		return $output;
	}

	public function toc($html)
	{
		$items = [];

		if (preg_match_all('/<h([1-3]) id="([^"]+)">(.*?)<\/h\1>/s', (string) $html, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$items[] = [
					'level' => (int) $match[1],
					'id' => $match[2],
					'text' => trim(strip_tags($match[3])),
				];
			}
		}

		return $items;
	}

	public function title($markdown, $fallback = '')
	{
		if (preg_match('/^#\s+(.+)$/m', (string) $markdown, $match)) {
			return trim($match[1]);
		}

		return $fallback;
	}

	private function heading($level, $text, $linkResolver)
	{
		$id = $this->slug($text);
		$base = $id;
		$n = 2;

		while (isset($this->slugs[$id])) {
			$id = $base . '-' . $n;
			$n++;
		}

		$this->slugs[$id] = true;

		return '<h' . $level . ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . $this->inline($text, $linkResolver) . '</h' . $level . '>';
	}

	private function isTable($block)
	{
		$lines = preg_split("/\n/", $block);

		return count($lines) >= 2 && strpos($lines[0], '|') !== false && preg_match('/^\s*\|?\s*:?-{3,}/', $lines[1]);
	}

	private function table($block, $linkResolver)
	{
		$lines = preg_split("/\n/", $block);
		$head = $this->tableRow(array_shift($lines), $linkResolver, true);
		array_shift($lines);

		$rows = [];

		foreach ($lines as $line) {
			if (trim($line) === '') {
				continue;
			}

			$rows[] = $this->tableRow($line, $linkResolver, false);
		}

		return '<div class="docs-table-wrap"><table><thead>' . $head . '</thead><tbody>' . implode('', $rows) . '</tbody></table></div>';
	}

	private function tableRow($line, $linkResolver, $header)
	{
		$line = trim($line);
		$line = trim($line, '|');
		$cells = array_map('trim', explode('|', $line));
		$tag = $header ? 'th' : 'td';
		$html = '<tr>';

		foreach ($cells as $cell) {
			$html .= '<' . $tag . '>' . $this->inline($cell, $linkResolver) . '</' . $tag . '>';
		}

		return $html . '</tr>';
	}

	private function listBlock($block, $linkResolver)
	{
		$lines = preg_split("/\n/", $block);
		$items = [];

		foreach ($lines as $line) {
			if (preg_match('/^(\s*)([-*]|\d+\.)\s+(.*)$/', $line, $match)) {
				$items[] = [
					'indent' => (int) floor(strlen($match[1]) / 2),
					'ordered' => $match[2] !== '-' && $match[2] !== '*',
					'text' => $match[3],
				];
			} elseif ($items) {
				$items[count($items) - 1]['text'] .= ' ' . trim($line);
			}
		}

		return $this->renderList($items, 0, $linkResolver);
	}

	private function renderList(array $items, $index, $linkResolver)
	{
		if (!isset($items[$index])) {
			return '';
		}

		$ordered = $items[$index]['ordered'];
		$indent = $items[$index]['indent'];
		$tag = $ordered ? 'ol' : 'ul';
		$html = '<' . $tag . '>';

		for ($i = $index; $i < count($items); $i++) {
			$item = $items[$i];

			if ($item['indent'] < $indent) {
				break;
			}

			if ($item['indent'] > $indent) {
				continue;
			}

			$html .= '<li>' . $this->inline($item['text'], $linkResolver);

			$next = $i + 1;

			if (isset($items[$next]) && $items[$next]['indent'] > $indent) {
				$html .= $this->renderList($items, $next, $linkResolver);
			}

			$html .= '</li>';
		}

		return $html . '</' . $tag . '>';
	}

	private function inline($text, $linkResolver)
	{
		$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

		$text = preg_replace_callback('/`([^`]+)`/', function ($match) {
			return '<code>' . $match[1] . '</code>';
		}, $text);

		$text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($match) use ($linkResolver) {
			$href = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');

			if (is_callable($linkResolver)) {
				$href = $linkResolver($href);
			}

			return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $match[1] . '</a>';
		}, $text);

		$text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
		$text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);

		return $text;
	}

	private function slug($text)
	{
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		$text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
		$text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
		$text = trim($text, '-');

		return $text !== '' ? $text : 'section';
	}
}
