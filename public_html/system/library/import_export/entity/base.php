<?php

namespace import_export\entity;

use import_export\Context;
use import_export\Mapper;
use import_export\Resolver;

abstract class Base implements EntityInterface
{
	protected $ctx;
	protected $mapper;
	protected $resolver;

	public function __construct(Context $ctx, Mapper $mapper, Resolver $resolver)
	{
		$this->ctx = $ctx;
		$this->mapper = $mapper;
		$this->resolver = $resolver;
	}

	public function identity(array $row)
	{
		return '';
	}

	protected function createRefs()
	{
		return $this->ctx->onMissing() === 'create';
	}

	protected function missingMode()
	{
		return $this->ctx->onMissing();
	}

	protected function result($action, $identity, $label = '', $error = '', $id = 0)
	{
		return [
			'entity' => $this->code(),
			'action' => $action,
			'identity' => $identity,
			'label' => $label !== '' ? $label : $identity,
			'error' => $error,
			'id' => (int) $id,
		];
	}

	protected function rowId(array $row, $key)
	{
		$raw = $this->rawId($row, $key);

		return $raw === null ? 0 : $raw;
	}

	protected function rawId(array $row, $key)
	{
		if (!$this->mapper->has($row, $key)) {
			return null;
		}

		return (int) $row[$key];
	}

	protected function isForceCreate(array $row, $key)
	{
		$raw = $this->rawId($row, $key);

		return $raw !== null && $raw <= 0;
	}

	protected function lookupId(array $row, $key, $table, $pk)
	{
		$raw = $this->rawId($row, $key);

		if ($raw === null || $raw === 0) {
			return 0;
		}

		if ($raw < 0) {
			$real = $this->resolver->alias($table, $raw);

			return $real > 0 ? $real : 0;
		}

		return $this->resolver->exists($table, $pk, $raw) ? $raw : -1;
	}

	protected function resolveFk($table, $id, $allow_local = false)
	{
		$id = (int) $id;

		if ($id === 0) {
			return 0;
		}

		return $this->resolver->resolveRef($table, $id, $allow_local);
	}

	protected function unknownRef($field, $id)
	{
		$id = (int) $id;

		if ($id === 0) {
			return '';
		}

		return $this->resolveFk($this->tableForField($field), $id, true) ? '' : 'Unknown ' . $field . ': ' . $id;
	}

	protected function tableForField($field)
	{
		$map = [
			'product_id' => 'product',
			'category_id' => 'category',
			'parent_id' => 'category',
			'manufacturer_id' => 'manufacturer',
			'attribute_id' => 'attribute',
			'attribute_group_id' => 'attribute_group',
		];

		return isset($map[$field]) ? $map[$field] : '';
	}

	protected function unknownIdError($field, $id)
	{
		return $this->result('error', (string) (int) $id, '', 'Unknown ' . $field . ': ' . (int) $id);
	}

	protected function finishWrite(array $row, $key, $table, array $result)
	{
		if (!empty($result['id']) && ($result['action'] === 'created' || $result['action'] === 'updated')) {
			$raw = $this->rawId($row, $key);

			if ($raw !== null && $raw < 0) {
				$this->resolver->rememberAlias($table, $raw, $result['id']);
			}
		}

		return $result;
	}

	protected function localizedName(array $row)
	{
		$name = $this->mapper->localized($row, 'name');

		if ($name) {
			$id = $this->ctx->defaultLanguageId();

			if (!empty($name[$id])) {
				return (string) $name[$id];
			}

			return (string) reset($name);
		}

		return trim((string) $this->mapper->scalar($row, 'name'));
	}
}
