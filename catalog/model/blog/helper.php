<?php
class ModelBlogHelper extends Model
{
	public function getModuleArticles(array $data = []): array
	{
		$this->load->model('blog/article');

		$limit = !empty($data['limit']) ? (int) $data['limit'] : 4;

		if ($limit < 1) {
			$limit = 4;
		}

		$article_ids = array_values(
			array_unique(array_filter(array_map('intval', (array) ($data['article_id'] ?? [])))),
		);
		$category_ids = array_values(
			array_unique(array_filter(array_map('intval', (array) ($data['category_id'] ?? [])))),
		);

		$articles = [];

		foreach ($article_ids as $article_id) {
			if (count($articles) >= $limit) {
				break;
			}

			$article = $this->model_blog_article->getArticle($article_id);

			if ($article) {
				$articles[$article_id] = $article;
			}
		}

		if (count($articles) >= $limit || !$category_ids) {
			return array_values($articles);
		}

		$exclude = $articles ? implode(',', array_keys($articles)) : '0';
		$remaining = $limit - count($articles);

		$sql =
			'SELECT p.article_id FROM ' .
			DB_PREFIX .
			'article p LEFT JOIN ' .
			DB_PREFIX .
			"article_to_store p2s ON (p.article_id = p2s.article_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" .
			(int) $this->config->get('config_store_id') .
			"' AND p.article_id NOT IN (" .
			$exclude .
			') AND p.article_id IN (SELECT a2c.article_id FROM ' .
			DB_PREFIX .
			'article_to_blog_category a2c LEFT JOIN ' .
			DB_PREFIX .
			'blog_category_path cp ON (a2c.blog_category_id = cp.blog_category_id) WHERE cp.path_id IN (' .
			implode(',', $category_ids) .
			')) ORDER BY p.date_added DESC, p.article_id DESC LIMIT ' .
			(int) $remaining;

		$query = $this->db->query($sql);

		foreach ($query->rows as $row) {
			$article = $this->model_blog_article->getArticle($row['article_id']);

			if ($article) {
				$articles[$row['article_id']] = $article;
			}
		}

		return array_values($articles);
	}

	public function getBlogName(string $fallback = ''): string
	{
		$name = trim((string) $this->config->get('configblog_name'));

		if ($name !== '') {
			return $name;
		}

		return $fallback !== '' ? $fallback : $this->language->get('text_blog');
	}

	public function prepareArticle(array $article, string $href = ''): array
	{
		$this->load->model('product/helper');

		$description_length = $this->model_product_helper->themeDescriptionLength();

		return [
			'article_id' => $article['article_id'],
			'thumb' => $this->model_product_helper->themeImage($article['image'] ?? '', 'product'),
			'name' => $article['name'],
			'description' =>
				utf8_substr(
					trim(strip_tags(html_entity_decode($article['description'], ENT_QUOTES, 'UTF-8'))),
					0,
					$description_length,
				) . '..',
			'date_added' => date($this->language->get('date_format_short'), strtotime($article['date_added'])),
			'href' => $href ?: $this->url->link('blog/article', 'article_id=' . $article['article_id']),
		];
	}

	public function getArticleGallery(array $article_info): array
	{
		$this->load->model('blog/article');
		$this->load->model('product/helper');

		$gallery = [];

		if (!empty($article_info['image'])) {
			$gallery[] = $this->buildGalleryImage($article_info['image']);
		}

		foreach ($this->model_blog_article->getArticleImages((int) $article_info['article_id']) as $result) {
			if (!empty($result['image'])) {
				$gallery[] = $this->buildGalleryImage($result['image']);
			}
		}

		return $gallery;
	}

	private function buildGalleryImage(string $image): array
	{
		return [
			'thumb' => $this->model_product_helper->themeImage($image, 'additional'),
			'preview' => $this->model_product_helper->themeImage($image, 'thumb'),
			'popup' => $this->model_product_helper->themeImage($image, 'popup', false),
		];
	}

	public function getArticleSorts(string $route, string $base_query, string $url = ''): array
	{
		$sorts = [
			[
				'text' => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'sort' => 'p.sort_order',
				'order' => 'ASC',
			],
			[
				'text' => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'sort' => 'pd.name',
				'order' => 'ASC',
			],
			[
				'text' => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'sort' => 'pd.name',
				'order' => 'DESC',
			],
			[
				'text' => $this->language->get('text_date_asc'),
				'value' => 'p.date_added-ASC',
				'sort' => 'p.date_added',
				'order' => 'ASC',
			],
			[
				'text' => $this->language->get('text_date_desc'),
				'value' => 'p.date_added-DESC',
				'sort' => 'p.date_added',
				'order' => 'DESC',
			],
		];

		if ($this->config->get('configblog_review_status')) {
			$sorts[] = [
				'text' => $this->language->get('text_rating_desc'),
				'value' => 'rating-DESC',
				'sort' => 'rating',
				'order' => 'DESC',
			];
			$sorts[] = [
				'text' => $this->language->get('text_rating_asc'),
				'value' => 'rating-ASC',
				'sort' => 'rating',
				'order' => 'ASC',
			];
		}

		$sorts[] = [
			'text' => $this->language->get('text_viewed_desc'),
			'value' => 'p.viewed-DESC',
			'sort' => 'p.viewed',
			'order' => 'DESC',
		];
		$sorts[] = [
			'text' => $this->language->get('text_viewed_asc'),
			'value' => 'p.viewed-ASC',
			'sort' => 'p.viewed',
			'order' => 'ASC',
		];

		foreach ($sorts as &$sort) {
			$sort['href'] = $this->url->link(
				$route,
				$base_query . '&sort=' . $sort['sort'] . '&order=' . $sort['order'] . $url,
			);

			unset($sort['sort'], $sort['order']);
		}

		unset($sort);

		return $sorts;
	}

	public function getPaginationData(string $route, string $query, int $total, int $page, int $limit): array
	{
		return [
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'text_prev' => $this->language->get('text_prev'),
			'text_next' => $this->language->get('text_next'),
			'url' => $this->url->link($route, ltrim($query . '&page={page}', '&')),
		];
	}

	public function buildArticleModule(array $results, array $setting, string $heading_default, string $prefix): array
	{
		$this->load->model('product/helper');

		$articles = [];

		foreach ($results as $result) {
			if (empty($result['article_id'])) {
				continue;
			}

			$articles[] = $this->prepareArticle($result);
		}

		$limit = !empty($setting['limit']) ? (int) $setting['limit'] : 4;

		if ($limit > 0) {
			$articles = array_slice($articles, 0, $limit);
		}

		if (!$articles) {
			return [];
		}

		static $module = 0;

		$id = $prefix . '-' . $module++;

		return [
			'heading_title' => $this->model_product_helper->getModuleHeading($setting, $heading_default),
			'heading_id' => $id,
			'articles' => $articles,
			'slider' => count($articles) > 4,
			'slider_config' => $this->model_product_helper->getSliderConfig($setting, $id),
			'button_more' => $this->language->get('button_more'),
		];
	}

	public function getAuthor(int $author_id): array
	{
		if ($author_id < 1) {
			return [];
		}

		$this->ensureAuthorSchema();

		$query = $this->db->query(
			'SELECT a.author_id, a.image, a.noindex, ad.name, ad.description, ad.meta_title, ad.meta_h1, ad.meta_description, ad.meta_keyword FROM `' .
				DB_PREFIX .
				'blog_author` a LEFT JOIN `' .
				DB_PREFIX .
				"blog_author_description` ad ON (a.author_id = ad.author_id) WHERE a.author_id = '" .
				(int) $author_id .
				"' AND a.status = '1' AND ad.language_id = '" .
				(int) $this->config->get('config_language_id') .
				"'",
		);

		if (!$query->num_rows || trim((string) $query->row['name']) === '') {
			return [];
		}

		$this->load->model('product/helper');

		$image = trim((string) $query->row['image']);
		$description = html_entity_decode($query->row['description'], ENT_QUOTES, 'UTF-8');
		$excerpt = trim(strip_tags($description));
		$length = $this->model_product_helper->themeDescriptionLength();

		if (utf8_strlen($excerpt) > $length) {
			$excerpt = utf8_substr($excerpt, 0, $length) . '..';
		}

		return [
			'author_id' => (int) $query->row['author_id'],
			'name' => $query->row['name'],
			'description' => $description,
			'excerpt' => $excerpt,
			'meta_title' => $query->row['meta_title'] ?? '',
			'meta_h1' => $query->row['meta_h1'] ?? '',
			'meta_description' => $query->row['meta_description'] ?? '',
			'meta_keyword' => $query->row['meta_keyword'] ?? '',
			'noindex' => isset($query->row['noindex']) ? (int) $query->row['noindex'] : 1,
			'image' => $image !== '' ? $this->model_product_helper->themeImage($image, 'author', false) : '',
			'thumb' => $image !== '' ? $this->model_product_helper->themeImage($image, 'author_page', false) : '',
			'href' => $this->url->link('blog/author', 'author_id=' . (int) $query->row['author_id']),
		];
	}

	public function getAuthors(): array
	{
		$this->ensureAuthorSchema();

		$query = $this->db->query(
			'SELECT a.author_id FROM `' .
				DB_PREFIX .
				'blog_author` a LEFT JOIN `' .
				DB_PREFIX .
				"blog_author_description` ad ON (a.author_id = ad.author_id) WHERE a.status = '1' AND ad.language_id = '" .
				(int) $this->config->get('config_language_id') .
				"' ORDER BY a.sort_order ASC, ad.name ASC",
		);

		$authors = [];

		foreach ($query->rows as $row) {
			$author = $this->getAuthor((int) $row['author_id']);

			if ($author) {
				$authors[] = $author;
			}
		}

		return $authors;
	}

	public function prepareArticleContent(string $html): array
	{
		$html = trim($html);

		if ($html === '') {
			return [
				'html' => '',
				'toc' => [],
			];
		}

		$dom = new \DOMDocument();
		libxml_use_internal_errors(true);

		$loaded = $dom->loadHTML(
			'<?xml encoding="UTF-8"><div id="article-toc-root">' . $html . '</div>',
			LIBXML_HTML_NODEFDTD,
		);

		libxml_clear_errors();

		if (!$loaded) {
			return [
				'html' => $html,
				'toc' => [],
			];
		}

		$xpath = new \DOMXPath($dom);
		$headings = $xpath->query('//*[@id="article-toc-root"]//h2 | //*[@id="article-toc-root"]//h3');
		$toc = [];
		$used = [];

		if ($headings) {
			foreach ($headings as $heading) {
				if (!$heading instanceof \DOMElement) {
					continue;
				}

				$text = trim(preg_replace('/\s+/u', ' ', $heading->textContent) ?? '');

				if ($text === '') {
					continue;
				}

				$id = trim($heading->getAttribute('id'));

				if ($id === '') {
					$id = $this->makeHeadingId($text, $used);
					$heading->setAttribute('id', $id);
				} else {
					$used[$id] = true;
				}

				$toc[] = [
					'id' => $id,
					'text' => $text,
					'level' => strtolower($heading->nodeName) === 'h3' ? 3 : 2,
				];
			}
		}

		$root = $xpath->query('//*[@id="article-toc-root"]')->item(0);
		$output = '';

		if ($root) {
			foreach ($root->childNodes as $child) {
				$output .= $dom->saveHTML($child);
			}
		} else {
			$output = $html;
		}

		return [
			'html' => $output,
			'toc' => $toc,
		];
	}

	public function ensureAuthorSchema(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		$this->db->query(
			'CREATE TABLE IF NOT EXISTS `' .
				DB_PREFIX .
				'blog_author` (
			`author_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`image` VARCHAR(255) NOT NULL DEFAULT \'\',
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			`noindex` TINYINT(1) NOT NULL DEFAULT 1,
			`sort_order` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`author_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci',
		);

		$this->db->query(
			'CREATE TABLE IF NOT EXISTS `' .
				DB_PREFIX .
				'blog_author_description` (
			`author_id` INT UNSIGNED NOT NULL,
			`language_id` INT UNSIGNED NOT NULL,
			`name` VARCHAR(255) NOT NULL DEFAULT \'\',
			`description` TEXT NOT NULL,
			`meta_title` VARCHAR(255) NOT NULL DEFAULT \'\',
			`meta_h1` VARCHAR(255) NOT NULL DEFAULT \'\',
			`meta_description` TEXT NOT NULL,
			`meta_keyword` VARCHAR(255) NOT NULL DEFAULT \'\',
			PRIMARY KEY (`author_id`, `language_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci',
		);

		$this->addAuthorColumn('blog_author', 'noindex', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`');
		$this->addAuthorColumn(
			'blog_author_description',
			'meta_title',
			"VARCHAR(255) NOT NULL DEFAULT '' AFTER `description`",
		);
		$this->addAuthorColumn(
			'blog_author_description',
			'meta_h1',
			"VARCHAR(255) NOT NULL DEFAULT '' AFTER `meta_title`",
		);
		$this->addAuthorColumn('blog_author_description', 'meta_description', 'TEXT NOT NULL AFTER `meta_h1`');
		$this->addAuthorColumn(
			'blog_author_description',
			'meta_keyword',
			"VARCHAR(255) NOT NULL DEFAULT '' AFTER `meta_description`",
		);

		$column = $this->db->query('SHOW COLUMNS FROM `' . DB_PREFIX . "article` LIKE 'author_id'");

		if (!$column->num_rows) {
			$this->db->query(
				'ALTER TABLE `' .
					DB_PREFIX .
					'article` ADD `author_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `article_id`',
			);
		}
	}

	private function addAuthorColumn(string $table, string $column, string $definition): void
	{
		$check = $this->db->query('SHOW COLUMNS FROM `' . DB_PREFIX . $table . '`');
		$exists = false;

		foreach ($check->rows as $row) {
			if (strcasecmp((string) $row['Field'], $column) === 0) {
				$exists = true;
				break;
			}
		}

		if ($exists) {
			return;
		}

		try {
			$this->db->query('ALTER TABLE `' . DB_PREFIX . $table . '` ADD `' . $column . '` ' . $definition);
		} catch (\Exception $e) {
			if (strpos($e->getMessage(), 'Duplicate column') === false) {
				throw $e;
			}
		}
	}

	private function makeHeadingId(string $text, array &$used): string
	{
		$slug = utf8_strtolower(trim($text));
		$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
		$slug = trim((string) $slug, '-');

		if ($slug === '') {
			$slug = 'section';
		}

		if (utf8_strlen($slug) > 60) {
			$slug = rtrim(utf8_substr($slug, 0, 60), '-');
		}

		$base = $slug;
		$i = 2;

		while (isset($used[$slug])) {
			$slug = $base . '-' . $i++;
		}

		$used[$slug] = true;

		return $slug;
	}
}
