<?php

namespace import_export\format;

interface FormatInterface
{
	public function name();

	public function mime();

	public function extension();

	public function encode($entity, array $rows, array $languages);

	public function decode($content, $entity_hint = '');
}
