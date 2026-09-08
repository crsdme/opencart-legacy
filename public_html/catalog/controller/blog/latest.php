<?php
class ControllerBlogLatest extends Controller
{
	public function index()
	{
		$this->load->language('blog/latest');
		$this->load->model('blog/article');
		$this->load->model('blog/helper');
		$this->load->model('product/helper');

		$this->model_product_helper->applyNoindexByParams($this->request->get, ['sort', 'order', 'page', 'limit']);

		$sort = $this->request->get['sort'] ?? 'p.date_added';
		$order = $this->request->get['order'] ?? 'DESC';
		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = isset($this->request->get['limit'])
			? (int) $this->request->get['limit']
			: $this->model_product_helper->themeLimit();

		$configblog_html_h1 = $this->config->get('configblog_html_h1');
		$data['heading_title'] = !empty($configblog_html_h1)
			? $configblog_html_h1
			: $this->language->get('heading_title');

		$configblog_meta_title = $this->config->get('configblog_meta_title');

		if (!empty($configblog_meta_title)) {
			$this->document->setTitle($configblog_meta_title);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$this->document->setDescription($this->config->get('configblog_meta_description'));
		$this->document->setKeywords($this->config->get('configblog_meta_keyword'));

		$blog_name = $this->model_blog_helper->getBlogName($this->language->get('heading_title'));

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

		$data['button_more'] = $this->language->get('button_more');
		$data['continue'] = $this->url->link('common/home');

		$article_data = [
			'sort' => $sort,
			'order' => $order,
			'start' => ($page - 1) * $limit,
			'limit' => $limit,
		];

		$article_total = $this->model_blog_article->getTotalArticles($article_data);
		$results = $this->model_blog_article->getArticles($article_data);

		$data['articles'] = [];

		foreach ($results as $result) {
			$data['articles'][] = $this->model_blog_helper->prepareArticle($result);
		}

		$data['sorts'] = $this->model_blog_helper->getArticleSorts(
			'blog/latest',
			'',
			$this->model_product_helper->buildUrl($this->request->get, ['limit']),
		);

		$data['limits'] = $this->model_product_helper->getLimits(
			'blog/latest',
			'',
			$this->model_product_helper->buildUrl($this->request->get, ['sort', 'order']),
		);

		$pagination_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

		$data['pagination_data'] = $this->model_blog_helper->getPaginationData(
			'blog/latest',
			$pagination_url,
			$article_total,
			$page,
			$limit,
		);

		$this->model_product_helper->addPaginationLinks('blog/latest', '', $page, $limit, $article_total);

		$data['text_total_articles'] = $this->model_product_helper->plural(
			$article_total,
			$this->language->get('text_article_count_1'),
			$this->language->get('text_article_count_2'),
			$this->language->get('text_article_count_5'),
		);

		$data['view'] = 'blog/latest';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
