<?php

namespace import_export\entity;

interface EntityInterface
{
	public function code();

	public function schema();

	public function example();

	public function identity(array $row);

	public function find(array $row);

	public function read($id);

	public function exportAll(array $filter = []);

	public function preview(array $row);

	public function write(array $row);
}
