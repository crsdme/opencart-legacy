<?php
class ControllerBlogAuthor extends Controller
{
	private $error = array();

	public function index()
	{
		$this->load->language('blog/author');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('blog/author');
		$this->model_blog_author->install();
		$this->getList();
	}

	public function add()
	{
		$this->load->language('blog/author');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('blog/author');
		$this->model_blog_author->install();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_blog_author->addAuthor($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . $this->getUrl(), true));
		}

		$this->getForm();
	}

	public function edit()
	{
		$this->load->language('blog/author');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('blog/author');
		$this->model_blog_author->install();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_blog_author->editAuthor($this->request->get['author_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . $this->getUrl(), true));
		}

		$this->getForm();
	}

	public function delete()
	{
		$this->load->language('blog/author');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('blog/author');
		$this->model_blog_author->install();

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $author_id) {
				$this->model_blog_author->deleteAuthor($author_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . $this->getUrl(), true));
		}

		$this->getList();
	}

	protected function getList()
	{
		$sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'ad.name';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		$url = $this->getUrl();

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('blog/author/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('blog/author/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$filter_data = array(
			'sort' => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$author_total = $this->model_blog_author->getTotalAuthors();
		$results = $this->model_blog_author->getAuthors($filter_data);

		$this->load->model('tool/image');

		$data['authors'] = array();

		foreach ($results as $result) {
			if ($result['image'] && is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}

			$data['authors'][] = array(
				'author_id' => $result['author_id'],
				'name' => $result['name'],
				'image' => $image,
				'sort_order' => $result['sort_order'],
				'status' => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit' => $this->url->link('blog/author/edit', 'user_token=' . $this->session->data['user_token'] . '&author_id=' . $result['author_id'] . $url, true)
			);
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$url_order = $order == 'ASC' ? 'DESC' : 'ASC';
		$data['sort_name'] = $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . '&sort=ad.name&order=' . $url_order, true);
		$data['sort_sort_order'] = $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . '&sort=a.sort_order&order=' . $url_order, true);
		$data['sort_status'] = $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . '&sort=a.status&order=' . $url_order, true);

		$pagination = new Pagination();
		$pagination->total = $author_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($author_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($author_total - $this->config->get('config_limit_admin'))) ? $author_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $author_total, ceil($author_total / $this->config->get('config_limit_admin')));
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('blog/author_list', $data));
	}

	protected function getForm()
	{
		$data['text_form'] = !isset($this->request->get['author_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : array();
		$data['error_keyword'] = isset($this->error['keyword']) ? $this->error['keyword'] : '';
		$url = $this->getUrl();

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['author_id'])) {
			$data['action'] = $this->url->link('blog/author/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('blog/author/edit', 'user_token=' . $this->session->data['user_token'] . '&author_id=' . $this->request->get['author_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('blog/author', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['author_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$author_info = $this->model_blog_author->getAuthor($this->request->get['author_id']);
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['author_description'])) {
			$data['author_description'] = $this->request->post['author_description'];
		} elseif (isset($this->request->get['author_id'])) {
			$data['author_description'] = $this->model_blog_author->getAuthorDescriptions($this->request->get['author_id']);
		} else {
			$data['author_description'] = array();
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($author_info)) {
			$data['image'] = $author_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($author_info) && is_file(DIR_IMAGE . $author_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($author_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$this->load->model('setting/store');

		$data['stores'] = array();
		$data['stores'][] = array(
			'store_id' => 0,
			'name' => $this->language->get('text_default')
		);

		foreach ($this->model_setting_store->getStores() as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name' => $store['name']
			);
		}

		if (isset($this->request->post['author_seo_url'])) {
			$data['author_seo_url'] = $this->request->post['author_seo_url'];
		} elseif (isset($this->request->get['author_id'])) {
			$data['author_seo_url'] = $this->model_blog_author->getAuthorSeoUrls($this->request->get['author_id']);
		} else {
			$data['author_seo_url'] = array();
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($author_info)) {
			$data['sort_order'] = $author_info['sort_order'];
		} else {
			$data['sort_order'] = 0;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($author_info)) {
			$data['status'] = $author_info['status'];
		} else {
			$data['status'] = true;
		}

		if (isset($this->request->post['noindex'])) {
			$data['noindex'] = $this->request->post['noindex'];
		} elseif (isset($author_info['noindex'])) {
			$data['noindex'] = $author_info['noindex'];
		} else {
			$data['noindex'] = 1;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('blog/author_form', $data));
	}

	protected function validateForm()
	{
		if (!$this->user->hasPermission('modify', 'blog/author')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['author_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 2) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		if (!empty($this->request->post['author_seo_url'])) {
			$this->load->model('design/seo_url');

			foreach ($this->request->post['author_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						if (count(array_keys($language, $keyword)) > 1) {
							$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
						}

						$seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

						foreach ($seo_urls as $seo_url) {
							if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['author_id']) || ($seo_url['query'] != 'author_id=' . $this->request->get['author_id']))) {
								$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');
								break;
							}
						}
					}
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete()
	{
		if (!$this->user->hasPermission('modify', 'blog/author')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function getUrl()
	{
		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		return $url;
	}
}
