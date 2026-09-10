<?php

namespace import_export\format;

class Csv implements FormatInterface
{
	public function name()
	{
		return 'csv';
	}

	public function mime()
	{
		return 'text/csv';
	}

	public function extension()
	{
		return 'csv';
	}

	public function encode($entity, array $rows, array $languages, $help = '')
	{
		unset($entity, $languages);

		if (!$rows) {
			return "\xEF\xBB\xBF";
		}

		$headers = array_keys($rows[0]);
		$handle = fopen('php://temp', 'r+');
		fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

		if ($help !== '') {
			fwrite($handle, '# ' . str_replace(["\r", "\n"], ' ', (string) $help) . "\n");
		}

		fputcsv($handle, $headers, ',', '"');

		foreach ($rows as $row) {
			$line = [];

			foreach ($headers as $header) {
				$line[] = isset($row[$header]) ? $row[$header] : '';
			}

			fputcsv($handle, $line, ',', '"');
		}

		rewind($handle);
		$csv = stream_get_contents($handle);
		fclose($handle);

		return $csv;
	}

	public function decode($content, $entity_hint = '')
	{
		$content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
		$lines = preg_split("/\r\n|\n|\r/", $content);
		$rows = [];
		$headers = [];

		foreach ($lines as $line) {
			if (trim($line) === '' || strpos(ltrim($line), '#') === 0) {
				continue;
			}

			$cells = str_getcsv($line, ',', '"');

			if (!$headers) {
				$headers = array_map('trim', $cells);
				continue;
			}

			$row = [];

			foreach ($headers as $i => $header) {
				if ($header === '') {
					continue;
				}

				$row[$header] = isset($cells[$i]) ? $cells[$i] : '';
			}

			if ($this->rowEmpty($row)) {
				continue;
			}

			$rows[] = $row;
		}

		$entity = $entity_hint !== '' ? $entity_hint : 'product';
		$plural = $this->plural($entity);

		return [
			'entity' => $entity,
			'items' => [
				'manufacturers' => $plural === 'manufacturers' ? $rows : [],
				'attribute_groups' => $plural === 'attribute_groups' ? $rows : [],
				'attributes' => $plural === 'attributes' ? $rows : [],
				'categories' => $plural === 'categories' ? $rows : [],
				'products' => $plural === 'products' ? $rows : [],
			],
		];
	}

	private function plural($entity)
	{
		$map = [
			'manufacturer' => 'manufacturers',
			'attribute_group' => 'attribute_groups',
			'attribute' => 'attributes',
			'category' => 'categories',
			'product' => 'products',
			'bundle' => 'products',
		];

		return isset($map[$entity]) ? $map[$entity] : 'products';
	}

	private function rowEmpty(array $row)
	{
		foreach ($row as $value) {
			if (trim((string) $value) !== '') {
				return false;
			}
		}

		return true;
	}
}
