<?php
class ControllerBlogAuthor extends Controller
{
	public function index()
	{
		$this->load->language('blog/author');
		$this->load->model('blog/article');
		$this->load->model('blog/helper');
		$this->load->model('product/helper');
		$this->load->model('seo/meta');

		$author_id = isset($this->request->get['author_id']) ? (int) $this->request->get['author_id'] : 0;
		$author = $this->model_blog_helper->getAuthor($author_id);

		if (!$author) {
			$this->notFound($author_id);
			return;
		}

		$this->model_product_helper->applyNoindexByParams($this->request->get, ['sort', 'order', 'page', 'limit']);

		$sort = $this->request->get['sort'] ?? 'p.date_added';
		$order = $this->request->get['order'] ?? 'DESC';
		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = isset($this->request->get['limit'])
			? (int) $this->request->get['limit']
			: $this->model_product_helper->themeLimit();

		$seo = $this->model_seo_meta->build(
			$author,
			[
				'name' => $author['name'],
				'page' => $page,
			],
			'blog_author',
			'blog/author',
			'author_id=' . $author_id,
		);

		$this->model_seo_meta->apply($seo);

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
			[
				'text' => $author['name'],
				'href' => $author['href'],
			],
		];

		$data['heading_title'] = $seo['h1'];
		$data['author'] = $author;
		$data['thumb'] = $author['thumb'] ?: $author['image'];
		$data['button_more'] = $this->language->get('button_more');
		$data['continue'] = $this->url->link('blog/latest');

		$article_data = [
			'filter_author_id' => $author_id,
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

		$query = 'author_id=' . $author_id;

		$data['sorts'] = $this->model_blog_helper->getArticleSorts(
			'blog/author',
			$query,
			$this->model_product_helper->buildUrl($this->request->get, ['limit']),
		);

		$data['limits'] = $this->model_product_helper->getLimits(
			'blog/author',
			$query,
			$this->model_product_helper->buildUrl($this->request->get, ['sort', 'order']),
		);

		$pagination_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);
		$data['pagination_data'] = $this->model_blog_helper->getPaginationData(
			'blog/author',
			$query . $pagination_url,
			(int) $article_total,
			$page,
			$limit,
		);

		$this->model_product_helper->addPaginationLinks(
			'blog/author',
			$query,
			$page,
			$limit,
			(int) $article_total,
		);

		$data['view'] = 'blog/author';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	private function notFound(int $author_id): void
	{
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('blog/author', 'author_id=' . $author_id),
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
