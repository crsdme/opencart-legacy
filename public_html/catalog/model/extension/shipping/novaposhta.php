<?php

class ModelExtensionShippingNovaposhta extends Model
{
	private $client;

	public function getQuote($address)
	{
		$this->load->language('extension/shipping/novaposhta');
		$settings = $this->settings();
		$methods = isset($settings['methods']) && is_array($settings['methods']) ? $settings['methods'] : [];
		$quote_data = [];

		foreach (['department', 'poshtomat', 'doors'] as $code) {
			$method = isset($methods[$code]) && is_array($methods[$code]) ? $methods[$code] : [];

			if (empty($method['status']) || !$this->inGeoZone($address, $method)) {
				continue;
			}

			$cost = isset($method['cost']) ? (float) $method['cost'] : 0;
			$tax = isset($method['tax_class_id']) ? (int) $method['tax_class_id'] : 0;

			$quote_data[$code] = [
				'code' => 'novaposhta.' . $code,
				'title' => $this->methodTitle($code, $method),
				'cost' => $cost,
				'tax_class_id' => $tax,
				'text' => $this->currency->format(
					$this->tax->calculate($cost, $tax, $this->config->get('config_tax')),
					$this->session->data['currency']
				),
				'extra' => 'index.php?route=extension/shipping/novaposhta/extra',
			];
		}

		if (!$quote_data) {
			return [];
		}

		return [
			'code' => 'novaposhta',
			'title' => $this->language->get('text_title'),
			'quote' => $quote_data,
			'sort_order' => $this->config->get('shipping_novaposhta_sort_order'),
			'error' => false,
		];
	}

	public function search($action, $query, $city = '', $city_ref = '', $type = 'department')
	{
		if ($action === 'cities') {
			return $this->searchCities($query, $type);
		}

		if ($action === 'warehouses') {
			return $this->searchWarehouses($query, $city, $type);
		}

		if ($action === 'streets') {
			return $this->searchStreets($query, $city, $city_ref);
		}

		return [];
	}

	private function searchCities($query, $type)
	{
		$settings = $this->settings();
		$query = trim((string) $query);

		if ($type === 'doors' && !empty($settings['settlements'])) {
			return $this->onlineCities($query);
		}

		if ($query === '') {
			return $this->defaultCities();
		}

		$field = $this->client()->descriptionField();
		$column = $field === 'DescriptionRu' ? 'description_ru' : 'description';
		$search = $this->db->escape($query);
		$rows = $this->db->query("SELECT `ref`, `description`, `description_ru` FROM `" . DB_PREFIX . "novaposhta_cities`
			WHERE `description` LIKE '" . $search . "%' OR `description_ru` LIKE '" . $search . "%'
			ORDER BY `" . $column . "`
			LIMIT 20")->rows;

		return $this->mapCities($rows);
	}

	private function onlineCities($query)
	{
		if (mb_strlen($query) < 2) {
			return $this->defaultCities();
		}

		$items = [];

		foreach ($this->client()->searchSettlements($query) as $row) {
			$name = isset($row['MainDescription']) ? trim((string) $row['MainDescription']) : '';
			$label = isset($row['Present']) ? trim((string) $row['Present']) : $name;

			if ($name === '') {
				continue;
			}

			$items[] = [
				'id' => isset($row['Ref']) ? $row['Ref'] : '',
				'value' => $name,
				'label' => $label !== '' ? $label : $name,
			];
		}

		return $items;
	}

	private function searchWarehouses($query, $city, $type)
	{
		$city = trim((string) $city);

		if ($city === '') {
			return [];
		}

		$field = $this->client()->descriptionField();
		$column = $field === 'DescriptionRu' ? 'description_ru' : 'description';
		$city_sql = $this->db->escape($city);
		$sql = "SELECT `ref`, `description`, `description_ru` FROM `" . DB_PREFIX . "novaposhta_departments`
			WHERE (`city_description` = '" . $city_sql . "' OR `city_description_ru` = '" . $city_sql . "')";

		if ($type === 'poshtomat') {
			$sql .= " AND `category` = 'Postomat'";
		} else {
			$sql .= " AND `category` <> 'Postomat'";
		}

		if ($query !== '') {
			$search = $this->db->escape($query);
			$sql .= " AND (`description` LIKE '%" . $search . "%' OR `description_ru` LIKE '%" . $search . "%')";
		}

		$sql .= " ORDER BY `number`+0, `" . $column . "` LIMIT 40";
		$rows = $this->db->query($sql)->rows;
		$items = [];

		foreach ($rows as $row) {
			$name = $field === 'DescriptionRu' ? $row['description_ru'] : $row['description'];
			$items[] = [
				'id' => $row['ref'],
				'value' => $name,
				'label' => $name,
			];
		}

		return $items;
	}

	private function searchStreets($query, $city, $city_ref)
	{
		$settings = $this->settings();

		if (empty($settings['streets'])) {
			return [];
		}

		$ref = trim((string) $city_ref);

		if ($ref === '' && $city !== '') {
			$settlements = $this->client()->searchSettlements($city, 1);
			$ref = isset($settlements[0]['Ref']) ? $settlements[0]['Ref'] : '';
		}

		$items = [];

		foreach ($this->client()->searchStreets($ref, $query) as $row) {
			$name = isset($row['Present']) ? trim((string) $row['Present']) : '';

			if ($name === '') {
				$name = isset($row['SettlementStreetDescription']) ? trim((string) $row['SettlementStreetDescription']) : '';
			}

			if ($name === '') {
				continue;
			}

			$items[] = [
				'id' => isset($row['SettlementStreetRef']) ? $row['SettlementStreetRef'] : '',
				'value' => $name,
				'label' => $name,
			];
		}

		return $items;
	}

	private function defaultCities()
	{
		$names = [
			['Київ', 'Киев'],
			['Харків', 'Харьков'],
			['Дніпро', 'Днепр'],
			['Одеса', 'Одесса'],
			['Львів', 'Львов'],
			['Запоріжжя', 'Запорожье'],
		];
		$ru = $this->client()->descriptionField() === 'DescriptionRu';
		$items = [];

		foreach ($names as $pair) {
			$name = $ru ? $pair[1] : $pair[0];
			$items[] = [
				'id' => '',
				'value' => $name,
				'label' => $name,
			];
		}

		return $items;
	}

	private function mapCities(array $rows)
	{
		$ru = $this->client()->descriptionField() === 'DescriptionRu';
		$items = [];

		foreach ($rows as $row) {
			$name = $ru ? $row['description_ru'] : $row['description'];
			$items[] = [
				'id' => $row['ref'],
				'value' => $name,
				'label' => $name,
			];
		}

		return $items;
	}

	private function methodTitle($code, array $method)
	{
		$lang = (int) $this->config->get('config_language_id');
		$names = isset($method['name']) && is_array($method['name']) ? $method['name'] : [];
		$custom = isset($names[$lang]) ? trim((string) $names[$lang]) : '';

		return $custom !== '' ? $custom : $this->language->get('text_' . $code);
	}

	private function inGeoZone(array $address, array $method)
	{
		$geo = isset($method['geo_zone_id']) ? (int) $method['geo_zone_id'] : 0;

		if (!$geo) {
			return true;
		}

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone`
			WHERE geo_zone_id = '" . $geo . "'
			AND country_id = '" . (int) $address['country_id'] . "'
			AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

		return (bool) $query->num_rows;
	}

	private function settings()
	{
		$saved = $this->config->get('shipping_novaposhta');

		return is_array($saved) ? $saved : [];
	}

	private function client()
	{
		if (!$this->client) {
			$this->client = new \Custom\NovaPoshta($this->registry);
		}

		return $this->client;
	}
}
