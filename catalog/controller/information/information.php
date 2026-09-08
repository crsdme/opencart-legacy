<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerInformationInformation extends Controller
{
	public function index()
	{
		$this->load->language('information/information');

		$this->load->model('catalog/information');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home'),
		];

		if (isset($this->request->get['information_id'])) {
			$information_id = (int) $this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$this->load->model('seo/meta');

			$seo = $this->model_seo_meta->build(
				$information_info,
				[
					'name' => $information_info['title'],
				],
				'information',
				'information/information',
				'information_id=' . $information_id
			);

			$this->model_seo_meta->apply($seo);
			$data['heading_title'] = $seo['h1'];

			$data['breadcrumbs'][] = [
				'text' => $information_info['title'],
				'href' => $this->url->link('information/information', 'information_id=' . $information_id),
			];

			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

			$data['continue'] = $this->url->link('common/home');

			$data['view'] = 'information/information';
			$this->response->setOutput($this->load->controller('common/layout', $data));
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('information/information', 'information_id=' . $information_id),
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

	public function agree()
	{
		$this->load->model('catalog/information');

		if (isset($this->request->get['information_id'])) {
			$information_id = (int) $this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$output = '';

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$output .= html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8') . "\n";
		}

		$this->response->addHeader('X-Robots-Tag: noindex');

		$this->response->setOutput($output);
	}
}
