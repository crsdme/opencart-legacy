<?php

class ControllerExtensionImportExportJob extends Controller
{
	public function index()
	{
		$data = $this->boot('job');
		$job = new \import_export\Job($this->db);
		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = (int) $this->config->get('config_limit_admin');

		if ($limit < 1) {
			$limit = 20;
		}

		if ($page < 1) {
			$page = 1;
		}

		$total = $job->getTotal();
		$results = $job->getJobs(($page - 1) * $limit, $limit);
		$data['jobs'] = [];

		foreach ($results as $row) {
			$data['jobs'][] = [
				'job_id' => $row['job_id'],
				'date_added' => $row['date_added'],
				'action' => $this->language->get('text_action_' . $row['action']),
				'entity' => $row['entity'],
				'format' => $row['format'],
				'filename' => $row['filename'],
				'status' => $this->language->get('text_status_' . $row['status']),
				'status_code' => $row['status'],
				'created' => $row['created'],
				'updated' => $row['updated'],
				'skipped' => $row['skipped'],
				'errors' => $row['errors'],
				'message' => $row['message'],
				'delete' => $this->url->link('extension/import_export/job/delete', 'user_token=' . $data['user_token'] . '&job_id=' . $row['job_id'], true),
			];
		}

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/import_export/job', 'user_token=' . $data['user_token'] . '&page={page}', true);
		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		foreach ([
			'text_tab_job', 'text_no_results', 'text_confirm', 'column_id', 'column_date',
			'column_action', 'column_entity', 'column_format', 'column_file', 'column_status',
			'column_created', 'column_updated', 'column_skipped', 'column_errors', 'column_message', 'button_delete',
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		$this->response->setOutput($this->load->view('extension/import_export/job', $data));
	}

	public function delete()
	{
		$this->load->language('extension/import_export/import_export');

		if (!$this->user->hasPermission('modify', 'extension/import_export')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/import_export/job', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$job_id = isset($this->request->get['job_id']) ? (int) $this->request->get['job_id'] : 0;

		if ($job_id) {
			$job = new \import_export\Job($this->db);
			$job->delete($job_id);
			$this->session->data['success'] = $this->language->get('text_deleted');
		}

		$this->response->redirect($this->url->link('extension/import_export/job', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function boot($active)
	{
		$this->load->language('extension/import_export/import_export');
		$this->document->setTitle($this->language->get('heading_title'));
		$engine = new \import_export\Engine($this->registry);
		$token = $this->session->data['user_token'];
		$tabs = $engine->tabs($this->url, $token, $active);

		foreach ($tabs as &$tab) {
			$tab['name'] = $this->language->get($tab['text']);
		}

		$data = [];
		$data['user_token'] = $token;
		$data['tabs'] = $tabs;
		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/import_export/dashboard', 'user_token=' . $token, true)],
		];
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
		unset($this->session->data['error']);
		$data['can_modify'] = $this->user->hasPermission('modify', 'extension/import_export');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		return $data;
	}
}
