<?php
class ControllerBlogCategory extends Controller
{
	public function index()
	{
		$this->load->language('blog/category');
		$this->load->model('blog/category');
		$this->load->model('blog/article');
		$this->load->model('blog/helper');
		$this->load->model('product/helper');
		$this->load->model('seo/meta');

		$this->model_product_helper->applyNoindexByParams($this->request->get, ['sort', 'order', 'page', 'limit']);

		$sort = $this->request->get['sort'] ?? 'p.date_added';
		$order = $this->request->get['order'] ?? 'DESC';
		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = isset($this->request->get['limit'])
			? (int) $this->request->get['limit']
			: $this->model_product_helper->themeLimit();

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

		$path_param = $this->request->get['blog_category_id'] ?? '';
		$blog_category_id = 0;

		if ($path_param) {
			$breadcrumb_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);
			$parts = explode('_', (string) $path_param);
			$blog_category_id = (int) array_pop($parts);
			$path = '';

			foreach ($parts as $path_id) {
				$path = $path ? $path . '_' . (int) $path_id : (int) $path_id;
				$category_info = $this->model_blog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = [
						'text' => $category_info['name'],
						'href' => $this->url->link('blog/category', 'blog_category_id=' . $path . $breadcrumb_url),
					];
				}
			}
		}

		$category_info = $this->model_blog_category->getCategory($blog_category_id);

		if (!$category_info) {
			$this->notFound();
			return;
		}

		$data['button_more'] = $this->language->get('button_more');
		$data['continue'] = $this->url->link('common/home');

		$data['breadcrumbs'][] = [
			'text' => $category_info['name'],
			'href' => $this->url->link('blog/category', 'blog_category_id=' . $path_param),
		];

		if ($category_info['image']) {
			$data['thumb'] = $this->model_product_helper->themeImage($category_info['image'], 'category');
		} else {
			$data['thumb'] = '';
		}

		$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');

		$url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

		$data['categories'] = [];

		foreach ($this->model_blog_category->getCategories($blog_category_id) as $result) {
			$filter_data = [
				'filter_blog_category_id' => $result['blog_category_id'],
				'filter_sub_category' => true,
			];

			$data['categories'][] = [
				'name' =>
					$result['name'] .
					($this->config->get('configblog_article_count')
						? ' (' . $this->model_blog_article->getTotalArticles($filter_data) . ')'
						: ''),
				'href' => $this->url->link(
					'blog/category',
					'blog_category_id=' . $path_param . '_' . $result['blog_category_id'] . $url,
				),
			];
		}

		$article_data = [
			'filter_blog_category_id' => $blog_category_id,
			'sort' => $sort,
			'order' => $order,
			'start' => ($page - 1) * $limit,
			'limit' => $limit,
		];

		$article_total = $this->model_blog_article->getTotalArticles($article_data);
		$results = $this->model_blog_article->getArticles($article_data);
		$article_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

		$data['articles'] = [];

		foreach ($results as $result) {
			$href = $this->url->link(
				'blog/article',
				'blog_category_id=' . $path_param . '&article_id=' . $result['article_id'] . $article_url,
			);
			$data['articles'][] = $this->model_blog_helper->prepareArticle($result, $href);
		}

		$base_query = 'blog_category_id=' . $path_param;

		$data['sorts'] = $this->model_blog_helper->getArticleSorts(
			'blog/category',
			$base_query,
			$this->model_product_helper->buildUrl($this->request->get, ['limit']),
		);

		$data['limits'] = $this->model_product_helper->getLimits(
			'blog/category',
			$base_query,
			$this->model_product_helper->buildUrl($this->request->get, ['sort', 'order']),
		);

		$pagination_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

		$data['pagination_data'] = $this->model_blog_helper->getPaginationData(
			'blog/category',
			$base_query . $pagination_url,
			$article_total,
			$page,
			$limit,
		);

		$this->model_product_helper->addPaginationLinks(
			'blog/category',
			$base_query,
			$page,
			$limit,
			$article_total,
		);

		$data['text_total_articles'] = $this->model_product_helper->plural(
			$article_total,
			$this->language->get('text_article_count_1'),
			$this->language->get('text_article_count_2'),
			$this->language->get('text_article_count_5'),
		);

		$seo = $this->model_seo_meta->build(
			$category_info,
			[
				'name' => $category_info['name'],
				'count' => $data['text_total_articles'],
				'page' => $page,
			],
			'blog_category',
			'blog/category',
			'blog_category_id=' . $path_param
		);

		$this->model_seo_meta->apply($seo);
		$data['heading_title'] = $seo['h1'];

		$data['view'] = 'blog/category';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	private function notFound(): void
	{
		$url = $this->model_product_helper->buildUrl($this->request->get, [
			'blog_category_id',
			'sort',
			'order',
			'page',
			'limit',
		]);

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('blog/category', $url),
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
