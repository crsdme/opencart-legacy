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
		return (int) $this->mapper->scalar($row, $key, 0);
	}

	protected function lookupId(array $row, $key, $table, $pk)
	{
		$id = $this->rowId($row, $key);

		if ($id < 1) {
			return 0;
		}

		return $this->resolver->exists($table, $pk, $id) ? $id : -1;
	}

	protected function unknownIdError($field, $id)
	{
		return $this->result('error', (string) (int) $id, '', 'Unknown ' . $field . ': ' . (int) $id);
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
