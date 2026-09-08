<?php

namespace Custom;

class NovaPoshta
{
	private $registry;
	private $api_url = 'https://api.novaposhta.ua/v2.0/json/';
	public $error = [];

	public function __construct($registry)
	{
		$this->registry = $registry;
	}

	public function __get($name)
	{
		return $this->registry->get($name);
	}

	public function settings()
	{
		$saved = $this->config->get('shipping_novaposhta');

		return is_array($saved) ? $saved : [];
	}

	public function key()
	{
		$settings = $this->settings();

		return isset($settings['key_api']) ? trim((string) $settings['key_api']) : '';
	}

	public function descriptionField()
	{
		$code = $this->language->get('code');

		return ($code === 'ru' || $code === 'ru-ru') ? 'DescriptionRu' : 'Description';
	}

	public function api($model, $method, array $properties = [])
	{
		$this->error = [];
		$request = [
			'apiKey' => $this->key(),
			'modelName' => $model,
			'calledMethod' => $method,
		];

		if ($properties) {
			$request['methodProperties'] = $properties;
		}

		$ch = curl_init($this->api_url);
		curl_setopt_array($ch, [
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($request),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		]);
		$response = curl_exec($ch);

		if ($response === false) {
			$this->error[] = curl_error($ch);
			curl_close($ch);

			return [];
		}

		curl_close($ch);
		$json = json_decode($response, true);

		if (!empty($json['errors']) && is_array($json['errors'])) {
			foreach ($json['errors'] as $error) {
				$this->error[] = is_array($error) ? implode(' ', $error) : (string) $error;
			}
		}

		if (empty($json['success']) || empty($json['data']) || !is_array($json['data'])) {
			return [];
		}

		return $json['data'];
	}

	public function searchSettlements($search, $limit = 20)
	{
		$search = trim((string) $search);

		if ($search === '') {
			return [];
		}

		$result = $this->api('Address', 'searchSettlements', [
			'CityName' => $search,
			'Limit' => (string) $limit,
		]);

		return !empty($result[0]['Addresses']) && is_array($result[0]['Addresses'])
			? $result[0]['Addresses']
			: [];
	}

	public function searchStreets($settlement_ref, $search, $limit = 20)
	{
		$settlement_ref = trim((string) $settlement_ref);
		$search = trim((string) $search);

		if ($settlement_ref === '' || $search === '') {
			return [];
		}

		$result = $this->api('Address', 'searchSettlementStreets', [
			'SettlementRef' => $settlement_ref,
			'StreetName' => $search,
			'Limit' => (string) $limit,
		]);

		return !empty($result[0]['Addresses']) && is_array($result[0]['Addresses'])
			? $result[0]['Addresses']
			: [];
	}

	public function sync($type)
	{
		if ($type === 'regions') {
			return $this->syncRegions();
		}

		if ($type === 'cities') {
			return $this->syncCities();
		}

		if ($type === 'departments') {
			return $this->syncDepartments();
		}

		return 0;
	}

	public function stats()
	{
		$rows = $this->db->query("SELECT `type`, `updated_at`, `amount` FROM `" . DB_PREFIX . "novaposhta_meta`")->rows;
		$stats = [];

		foreach ($rows as $row) {
			$stats[$row['type']] = [
				'updated_at' => $row['updated_at'],
				'amount' => (int) $row['amount'],
			];
		}

		return $stats;
	}

	private function syncRegions()
	{
		$data = $this->api('Address', 'getAreas');

		if (!$data) {
			return 0;
		}

		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "novaposhta_regions`");
		$count = 0;

		foreach ($data as $row) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "novaposhta_regions` SET
				`ref` = '" . $this->db->escape($row['Ref']) . "',
				`description` = '" . $this->db->escape($this->text($row, 'Description')) . "',
				`description_ru` = '" . $this->db->escape($this->text($row, 'DescriptionRu')) . "'
			");
			$count++;
		}

		$this->saveStat('regions', $count);

		return $count;
	}

	private function syncCities()
	{
		$count = 0;

		for ($page = 1; ; $page++) {
			$data = $this->api('Address', 'getCities', [
				'Page' => (string) $page,
				'Limit' => '500',
			]);

			if (!$data) {
				break;
			}

			if ($page === 1) {
				$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "novaposhta_cities`");
			}

			foreach ($data as $row) {
				$ua = $this->text($row, 'Description');
				$ru = $this->text($row, 'DescriptionRu');

				if ($ua === '' && $ru === '') {
					continue;
				}

				$this->db->query("INSERT INTO `" . DB_PREFIX . "novaposhta_cities` SET
					`ref` = '" . $this->db->escape($row['Ref']) . "',
					`description` = '" . $this->db->escape($ua !== '' ? $ua : $ru) . "',
					`description_ru` = '" . $this->db->escape($ru !== '' ? $ru : $ua) . "',
					`area` = '" . $this->db->escape(isset($row['Area']) ? $row['Area'] : '') . "'
				");
				$count++;
			}
		}

		if ($count) {
			$this->saveStat('cities', $count);
		}

		return $count;
	}

	private function syncDepartments()
	{
		$count = 0;

		for ($page = 1; ; $page++) {
			$data = $this->api('Address', 'getWarehouses', [
				'Page' => (string) $page,
				'Limit' => '500',
			]);

			if (!$data) {
				break;
			}

			if ($page === 1) {
				$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "novaposhta_departments`");
			}

			foreach ($data as $row) {
				$ua = $this->text($row, 'Description');
				$ru = $this->text($row, 'DescriptionRu');

				if ($ua === '' && $ru === '') {
					continue;
				}

				$this->db->query("INSERT INTO `" . DB_PREFIX . "novaposhta_departments` SET
					`ref` = '" . $this->db->escape($row['Ref']) . "',
					`description` = '" . $this->db->escape($ua !== '' ? $ua : $ru) . "',
					`description_ru` = '" . $this->db->escape($ru !== '' ? $ru : $ua) . "',
					`city_ref` = '" . $this->db->escape(isset($row['CityRef']) ? $row['CityRef'] : '') . "',
					`city_description` = '" . $this->db->escape($this->text($row, 'CityDescription')) . "',
					`city_description_ru` = '" . $this->db->escape($this->text($row, 'CityDescriptionRu')) . "',
					`category` = '" . $this->db->escape(isset($row['CategoryOfWarehouse']) ? $row['CategoryOfWarehouse'] : '') . "',
					`number` = '" . (int) (isset($row['Number']) ? $row['Number'] : 0) . "'
				");
				$count++;
			}
		}

		if ($count) {
			$this->saveStat('departments', $count);
		}

		return $count;
	}

	private function saveStat($type, $amount)
	{
		$this->db->query("INSERT INTO `" . DB_PREFIX . "novaposhta_meta` SET
			`type` = '" . $this->db->escape($type) . "',
			`updated_at` = NOW(),
			`amount` = '" . (int) $amount . "'
			ON DUPLICATE KEY UPDATE `updated_at` = NOW(), `amount` = '" . (int) $amount . "'
		");
	}

	private function text(array $row, $key)
	{
		return isset($row[$key]) ? trim((string) $row[$key]) : '';
	}
}
