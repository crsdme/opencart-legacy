<?php
class ModelSeoFaq extends Model
{
	public function attach(array $data, string $type, int $entity_id, array $entity, array $context = []): array
	{
		return $this->getPage($type, $entity_id, $entity, $data, $context);
	}

	public function getPage(string $type, int $entity_id, array $entity, array $data = [], array $context = []): array
	{
		$empty = [
			'title' => '',
			'items' => [],
		];

		if (!(int) $this->config->get('module_faq_status')) {
			return $empty;
		}

		if (!(int) $this->config->get('module_faq_' . $type)) {
			return $empty;
		}

		$page = (int) ($context['page'] ?? 1);

		if ($page > 1 && (int) $this->config->get('module_faq_first_page_only')) {
			return $empty;
		}

		if (!empty($context['filter'])) {
			return $empty;
		}

		if ($entity_id && !$this->isPageEnabled($type, $entity_id)) {
			return $empty;
		}

		$map = $this->tokens($type, $entity, $data, $context);
		$items = array_merge(
			$this->prepareItems($this->getItems($type, $entity_id), $map),
			$this->prepareItems($this->getItems($type, 0), $map)
		);

		if (!$items) {
			return $empty;
		}

		return [
			'title' => $this->title($type, $map),
			'items' => $items,
		];
	}

	private function getItems(string $type, int $entity_id): array
	{
		$language_id = (int) $this->config->get('config_language_id');

		$query = $this->db->query(
			"SELECT f.faq_id, f.sort_order, fd.question, fd.answer
			FROM `" . DB_PREFIX . "faq` f
			LEFT JOIN `" . DB_PREFIX . "faq_description` fd
				ON (f.faq_id = fd.faq_id AND fd.language_id = '" . $language_id . "')
			WHERE f.entity_type = '" . $this->db->escape($type) . "'
				AND f.entity_id = '" . (int) $entity_id . "'
				AND f.scope = 'page'
				AND f.status = '1'
				AND fd.question <> ''
				AND fd.answer <> ''
			ORDER BY f.sort_order ASC, f.faq_id ASC"
		);

		return $query->rows;
	}

	private function isPageEnabled(string $type, int $entity_id): bool
	{
		$query = $this->db->query(
			"SELECT `status` FROM `" . DB_PREFIX . "faq_page`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . (int) $entity_id . "'"
		);

		if (!$query->num_rows) {
			return true;
		}

		return (int) $query->row['status'] === 1;
	}

	private function prepareItems(array $rows, array $map): array
	{
		$items = [];

		foreach ($rows as $row) {
			$question = $this->replace($this->plain($row['question'] ?? ''), $map);
			$answer_html = $this->replace(
				html_entity_decode((string) ($row['answer'] ?? ''), ENT_QUOTES, 'UTF-8'),
				$map
			);
			$answer_text = $this->plain($answer_html);

			if ($question === '' || $answer_text === '') {
				continue;
			}

			$items[] = [
				'title' => $question,
				'content' => $answer_html,
				'text' => $answer_text,
			];
		}

		return $items;
	}

	private function title(string $type, array $map): string
	{
		$titles = $this->config->get('module_faq_title_' . $type);
		$language_id = (int) $this->config->get('config_language_id');
		$title = '';

		if (is_array($titles) && !empty($titles[$language_id])) {
			$title = html_entity_decode((string) $titles[$language_id], ENT_QUOTES, 'UTF-8');
		}

		if ($title === '') {
			$this->load->language('seo/faq');
			$title = $this->language->get('heading_' . $type);
		}

		return $this->replace($title, $map);
	}

	private function tokens(string $type, array $entity, array $data, array $context): array
	{
		$name = html_entity_decode((string) ($entity['name'] ?? ''), ENT_QUOTES, 'UTF-8');
		$heading = html_entity_decode((string) ($data['heading_title'] ?? $name), ENT_QUOTES, 'UTF-8');
		$meta = html_entity_decode((string) ($entity['meta_title'] ?? ''), ENT_QUOTES, 'UTF-8');
		$month = date('m');
		$year = date('Y');

		$map = [
			'{name}' => $name,
			'{heading_title}' => $heading,
			'{meta_title}' => $meta,
			'{month}' => $month,
			'{year}' => $year,
		];

		if ($type === 'product') {
			$price = (string) ($context['price'] ?? '');
			$category = (string) ($context['category_name'] ?? '');
			$manufacturer = html_entity_decode((string) ($entity['manufacturer'] ?? ''), ENT_QUOTES, 'UTF-8');

			$map['{product_name}'] = $name;
			$map['{price}'] = $price;
			$map['{product_price}'] = $price;
			$map['{manufacturer}'] = $manufacturer;
			$map['{model}'] = (string) ($entity['model'] ?? '');
			$map['{sku}'] = (string) ($entity['sku'] ?? '');
			$map['{category}'] = $category;
			$map['{category_name}'] = $category;
		} elseif ($type === 'category') {
			$map['{category_name}'] = $name;
		} elseif ($type === 'manufacturer') {
			$map['{manufacturer_name}'] = $name;
		}

		return $map;
	}

	private function replace(string $text, array $map): string
	{
		return strtr($text, $map);
	}

	private function plain(string $value): string
	{
		$text = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
		$text = strip_tags($text);
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim((string) $text);
	}
}
