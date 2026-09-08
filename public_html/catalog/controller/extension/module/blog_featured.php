<?php
class ControllerExtensionModuleBlogFeatured extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/blog_featured');
		$this->load->model('blog/helper');

		$results = $this->model_blog_helper->getModuleArticles([
			'article_id' => $setting['article'] ?? [],
			'category_id' => $setting['category'] ?? [],
			'limit' => !empty($setting['limit']) ? (int) $setting['limit'] : 4,
		]);

		$data = $this->model_blog_helper->buildArticleModule(
			$results,
			$setting,
			$this->language->get('heading_title'),
			'blog-featured',
		);

		if (!$data) {
			return '';
		}

		if (!empty($data['slider'])) {
			$this->document->addScript('catalog/view/theme/default/javascript/embla-carousel.js');
		}

		return $this->load->view('extension/module/blog_featured', $data);
	}
}
