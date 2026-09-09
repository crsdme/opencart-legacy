<?php
class ControllerBlogArticle extends Controller
{
	public function index()
	{
		$this->load->language('blog/article');
		$this->load->model('blog/article');
		$this->load->model('blog/category');
		$this->load->model('blog/helper');
		$this->load->model('product/helper');
		$this->load->model('seo/meta');
		$this->model_blog_helper->ensureAuthorSchema();

		$article_id = isset($this->request->get['article_id']) ? (int) $this->request->get['article_id'] : 0;
		$article_info = $this->model_blog_article->getArticle($article_id);

		if (!$article_info) {
			$this->notFound($article_id);
			return;
		}

		$blog_name = $this->model_blog_helper->getBlogName($this->language->get('text_blog'));

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $blog_name,
				'href' => $this->url->link('blog/latest'),
			],
		];

		if (isset($this->request->get['blog_category_id'])) {
			$blog_category_id = '';

			foreach (explode('_', $this->request->get['blog_category_id']) as $path_id) {
				$blog_category_id = $blog_category_id ? $blog_category_id . '_' . $path_id : $path_id;
				$category_info = $this->model_blog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = [
						'text' => $category_info['name'],
						'href' => $this->url->link('blog/category', 'blog_category_id=' . $blog_category_id),
					];
				}
			}
		}

		$data['breadcrumbs'][] = [
			'text' => $article_info['name'],
			'href' => $this->url->link('blog/article', 'article_id=' . $article_id),
		];

		$seo = $this->model_seo_meta->build(
			$article_info,
			[
				'name' => $article_info['name'],
			],
			'blog_article',
			'blog/article',
			'article_id=' . $article_id
		);

		$this->model_seo_meta->apply($seo);
		$data['heading_title'] = $seo['h1'];
		$data['article_id'] = $article_id;

		$content = $this->model_blog_helper->prepareArticleContent(
			html_entity_decode($article_info['description'], ENT_QUOTES, 'UTF-8'),
		);

		$data['description'] = $content['html'];
		$data['toc'] = $content['toc'];
		$data['author'] = $this->model_blog_helper->getAuthor((int) ($article_info['author_id'] ?? 0));
		$data['date_added'] = date($this->language->get('date_format_short'), strtotime($article_info['date_added']));
		$modified = $article_info['date_modified'] ?? '';

		if ($modified === '' || strpos($modified, '0000') === 0) {
			$modified = $article_info['date_added'];
		}

		$data['date_published'] = date('c', strtotime($article_info['date_added']));
		$data['date_modified'] = date('c', strtotime($modified));
		$data['viewed'] = (int) $article_info['viewed'];
		$data['gallery'] = $this->model_blog_helper->getArticleGallery($article_info);
		$data['button_more'] = $this->language->get('button_more');
		$data['text_login'] = sprintf(
			$this->language->get('text_login'),
			$this->url->link('account/login', '', true),
			$this->url->link('account/register', '', true),
		);

		$data['review_status'] = $this->config->get('configblog_review_status');
		$data['review_guest'] = $this->config->get('configblog_review_guest') || $this->customer->isLogged();
		$data['customer_name'] = $this->customer->isLogged()
			? $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
			: '';
		$data['reviews'] = sprintf($this->language->get('text_reviews'), (int) $article_info['reviews']);
		$data['rating'] = (int) $article_info['rating'];
		$data['tab_review'] = sprintf($this->language->get('tab_review'), (int) $article_info['reviews']);
		$data['review_url'] = $this->url->link('blog/article/review', 'article_id=' . $article_id);
		$data['write_url'] = 'index.php?route=blog/article/write&article_id=' . $article_id;
		$data['captcha'] = $this->getReviewCaptcha();

		$data['articles'] = [];

		foreach ($this->model_blog_article->getArticleRelated($article_id) as $result) {
			$data['articles'][] = $this->model_blog_helper->prepareArticle($result);
		}

		$data['products'] = [];

		foreach ($this->model_blog_article->getArticleRelatedProduct($article_id) as $result) {
			if (empty($result['product_id'])) {
				continue;
			}

			$data['products'][] = $this->model_product_helper->prepareProduct(
				$result,
				$this->url->link('product/product', 'product_id=' . $result['product_id']),
				'related',
			);
		}

		$data['download_status'] = $this->config->get('configblog_article_download');
		$data['downloads'] = [];

		foreach ($this->model_blog_article->getDownloads($article_id) as $result) {
			if (!file_exists(DIR_DOWNLOAD . $result['filename'])) {
				continue;
			}

			$size = filesize(DIR_DOWNLOAD . $result['filename']);
			$i = 0;
			$suffix = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

			while ($size / 1024 > 1) {
				$size = $size / 1024;
				$i++;
			}

			$data['downloads'][] = [
				'name' => $result['name'],
				'size' => round($size, 2) . ' ' . $suffix[$i],
				'href' => $this->url->link(
					'blog/article/download',
					'article_id=' . $article_id . '&download_id=' . $result['download_id'],
				),
			];
		}

		$this->model_blog_article->updateViewed($article_id);

		$data['view'] = 'blog/article';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	public function download()
	{
		$this->load->model('blog/article');

		$download_id = isset($this->request->get['download_id']) ? (int) $this->request->get['download_id'] : 0;
		$article_id = isset($this->request->get['article_id']) ? (int) $this->request->get['article_id'] : 0;
		$download_info = $this->model_blog_article->getDownload($article_id, $download_id);

		if (!$download_info) {
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		$file = DIR_DOWNLOAD . $download_info['filename'];
		$mask = basename($download_info['mask']);

		if (!headers_sent()) {
			if (file_exists($file)) {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . ($mask ? $mask : basename($file)) . '"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));

				readfile($file);
				exit();
			}

			exit('Error: Could not find file ' . $file . '!');
		}

		exit('Error: Headers already sent out!');
	}

	public function review()
	{
		$this->load->language('blog/article');
		$this->load->model('blog/review');
		$this->load->model('blog/helper');

		$article_id = isset($this->request->get['article_id']) ? (int) $this->request->get['article_id'] : 0;
		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = 5;

		$review_total = $this->model_blog_review->getTotalReviewsByArticleId($article_id);
		$results = $this->model_blog_review->getReviewsByArticleId($article_id, ($page - 1) * $limit, $limit);

		$data['reviews'] = [];

		foreach ($results as $result) {
			$data['reviews'][] = [
				'author' => $result['author'],
				'text' => nl2br($result['text']),
				'rating' => (int) $result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
			];
		}

		$data['pagination_data'] = $this->model_blog_helper->getPaginationData(
			'blog/article/review',
			'article_id=' . $article_id,
			$review_total,
			$page,
			$limit,
		);

		$this->response->setOutput($this->load->view('blog/review', $data));
	}

	public function write()
	{
		$this->load->language('blog/article');

		$json = [];

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (utf8_strlen($this->request->post['name']) < 3 || utf8_strlen($this->request->post['name']) > 25) {
				$json['error'] = $this->language->get('error_name');
			}

			if (utf8_strlen($this->request->post['text']) < 25 || utf8_strlen($this->request->post['text']) > 1000) {
				$json['error'] = $this->language->get('error_text');
			}

			if (
				empty($this->request->post['rating']) ||
				$this->request->post['rating'] < 0 ||
				$this->request->post['rating'] > 5
			) {
				$json['error'] = $this->language->get('error_rating');
			}

			if (
				$this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') &&
				in_array('review', (array) $this->config->get('config_captcha_page'))
			) {
				$captcha = $this->load->controller(
					'extension/captcha/' . $this->config->get('config_captcha') . '/validate',
				);

				if ($captcha) {
					$json['error'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('blog/review');
				$this->model_blog_review->addReview($this->request->get['article_id'], $this->request->post);
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function getReviewCaptcha(): string
	{
		if (
			$this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') &&
			in_array('review', (array) $this->config->get('config_captcha_page'))
		) {
			return $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
		}

		return '';
	}

	private function notFound(int $article_id): void
	{
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('blog/article', 'article_id=' . $article_id),
			],
		];

		$this->document->setTitle($this->language->get('text_error'));

		$data['heading_title'] = $this->language->get('text_error');
		$data['text_error'] = $this->language->get('text_error');
		$data['continue'] = $this->url->link('common/home');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$data['view'] = 'error/not_found';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
