<?php
class ControllerExtensionModuleBlogLatest extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/blog_latest');
		$this->load->model('blog/article');
		$this->load->model('blog/helper');

		$limit = !empty($setting['limit']) ? (int) $setting['limit'] : 4;
		$results = $this->model_blog_article->getLatestArticles($limit);
		$data = $this->model_blog_helper->buildArticleModule(
			$results ?: [],
			$setting,
			$this->language->get('heading_title'),
			'blog-latest',
		);

		if (!$data) {
			return '';
		}

		if (!empty($data['slider'])) {
			$this->document->addScript('catalog/view/theme/default/javascript/embla-carousel.js');
		}

		return $this->load->view('extension/module/blog_latest', $data);
	}
}
