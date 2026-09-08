<?php

class ControllerExtensionFaqEvent extends Controller
{
	public function productFormAfter(&$route, &$data, &$output)
	{
		$entity_id = isset($this->request->get['product_id']) ? (int) $this->request->get['product_id'] : 0;
		$this->injectForm($output, $data, 'product', $entity_id);
	}

	public function categoryFormAfter(&$route, &$data, &$output)
	{
		$entity_id = isset($this->request->get['category_id']) ? (int) $this->request->get['category_id'] : 0;
		$this->injectForm($output, $data, 'category', $entity_id);
	}

	public function manufacturerFormAfter(&$route, &$data, &$output)
	{
		$entity_id = isset($this->request->get['manufacturer_id']) ? (int) $this->request->get['manufacturer_id'] : 0;
		$this->injectForm($output, $data, 'manufacturer', $entity_id);
	}

	public function productAddAfter(&$route, &$args, &$output)
	{
		$this->save('product', (int) $output, isset($args[0]) && is_array($args[0]) ? $args[0] : []);
	}

	public function productEditAfter(&$route, &$args, &$output)
	{
		$this->save('product', isset($args[0]) ? (int) $args[0] : 0, isset($args[1]) && is_array($args[1]) ? $args[1] : []);
	}

	public function productDelete(&$route, &$args)
	{
		$this->delete('product', isset($args[0]) ? (int) $args[0] : 0);
	}

	public function categoryAddAfter(&$route, &$args, &$output)
	{
		$this->save('category', (int) $output, isset($args[0]) && is_array($args[0]) ? $args[0] : []);
	}

	public function categoryEditAfter(&$route, &$args, &$output)
	{
		$this->save('category', isset($args[0]) ? (int) $args[0] : 0, isset($args[1]) && is_array($args[1]) ? $args[1] : []);
	}

	public function categoryDelete(&$route, &$args)
	{
		$this->delete('category', isset($args[0]) ? (int) $args[0] : 0);
	}

	public function manufacturerAddAfter(&$route, &$args, &$output)
	{
		$this->save('manufacturer', (int) $output, isset($args[0]) && is_array($args[0]) ? $args[0] : []);
	}

	public function manufacturerEditAfter(&$route, &$args, &$output)
	{
		$this->save(
			'manufacturer',
			isset($args[0]) ? (int) $args[0] : 0,
			isset($args[1]) && is_array($args[1]) ? $args[1] : []
		);
	}

	public function manufacturerDelete(&$route, &$args)
	{
		$this->delete('manufacturer', isset($args[0]) ? (int) $args[0] : 0);
	}

	private function injectForm(&$output, &$data, $type, $entity_id)
	{
		if (!(int) $this->config->get('module_faq_status')) {
			return;
		}

		if (!(int) $this->config->get('module_faq_' . $type)) {
			return;
		}

		if (strpos((string) $output, 'id="tab-faq"') !== false) {
			return;
		}

		$this->load->language('extension/faq/faq');
		$this->load->model('extension/faq/faq');
		$this->load->model('localisation/language');

		$languages = !empty($data['languages'])
			? $data['languages']
			: $this->model_localisation_language->getLanguages();
		$languages = array_values($languages);

		$tab = $this->load->view('extension/faq/form_tab', [
			'languages' => $languages,
			'faq_items' => $this->model_extension_faq_faq->getItems($type, $entity_id),
			'faq_page_status' => $this->model_extension_faq_faq->getPageStatus($type, $entity_id),
			'text_enabled' => $this->language->get('text_enabled'),
			'text_disabled' => $this->language->get('text_disabled'),
			'text_page_status' => $this->language->get('text_page_status'),
			'help_page_status' => $this->language->get('help_page_status'),
			'text_entity_faq' => $this->language->get('text_entity_faq'),
			'help_entity_faq' => $this->language->get('help_entity_faq_' . $type),
			'help_placeholders' => $this->language->get('help_placeholders'),
			'help_placeholder_copy' => $this->language->get('help_placeholder_copy'),
			'text_copied' => $this->language->get('text_copied'),
			'placeholders' => $this->model_extension_faq_faq->getPlaceholders($type),
			'column_question' => $this->language->get('column_question'),
			'column_answer' => $this->language->get('column_answer'),
			'column_sort_order' => $this->language->get('column_sort_order'),
			'column_status' => $this->language->get('column_status'),
			'button_add' => $this->language->get('button_add'),
			'button_remove' => $this->language->get('button_remove'),
		]);

		$link = '<li><a href="#tab-faq" data-toggle="tab">' . $this->language->get('tab_faq') . '</a></li>';
		$output = preg_replace('/<li[^>]*>\s*<a href="#tab-design"/', $link . '$0', $output, 1);
		$output = preg_replace('/<div class="tab-pane" id="tab-design">/', $tab . '$0', $output, 1);
	}

	private function save($type, $entity_id, array $data)
	{
		if (!$entity_id) {
			return;
		}

		$this->load->model('extension/faq/faq');
		$this->model_extension_faq_faq->saveEntityFromPost($type, $entity_id, $data);
	}

	private function delete($type, $entity_id)
	{
		$this->load->model('extension/faq/faq');
		$this->model_extension_faq_faq->deleteEntity($type, $entity_id);
	}
}
