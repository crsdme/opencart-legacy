<?php

class ModelCheckoutFields extends Model
{
	public function all()
	{
		$saved = $this->config->get('module_checkout_fields');
		$saved = is_array($saved) ? $saved : [];

		if (!$saved) {
			$comment = $this->config->get('theme_default_checkout_comment');

			if ($comment === 'hidden') {
				$saved['comment'] = ['show' => 0, 'required' => 0];
			} elseif ($comment === 'required') {
				$saved['comment'] = ['show' => 1, 'required' => 1];
			}
		}

		$fields = [];

		foreach ($this->defaults() as $code => $field) {
			$row = isset($saved[$code]) && is_array($saved[$code]) ? $saved[$code] : [];
			$show = array_key_exists('show', $row) ? !empty($row['show']) : !empty($field['show']);
			$required = array_key_exists('required', $row)
				? !empty($row['required'])
				: !empty($field['required']);

			$fields[$code] = [
				'group' => $field['group'],
				'show' => $show ? 1 : 0,
				'required' => ($show && $required) ? 1 : 0,
			];
		}

		return $fields;
	}

	public function defaults()
	{
		return [
			'firstname' => [
				'group' => 'customer',
				'show' => 1,
				'required' => 1,
			],
			'lastname' => [
				'group' => 'customer',
				'show' => 0,
				'required' => 0,
			],
			'email' => [
				'group' => 'customer',
				'show' => 1,
				'required' => 0,
			],
			'telephone' => [
				'group' => 'customer',
				'show' => 1,
				'required' => 1,
			],
			'company' => [
				'group' => 'address',
				'show' => 0,
				'required' => 0,
			],
			'city' => [
				'group' => 'address',
				'show' => 1,
				'required' => 1,
			],
			'address_1' => [
				'group' => 'address',
				'show' => 1,
				'required' => 1,
			],
			'address_2' => [
				'group' => 'address',
				'show' => 0,
				'required' => 0,
			],
			'postcode' => [
				'group' => 'address',
				'show' => 0,
				'required' => 0,
			],
			'comment' => [
				'group' => 'comment',
				'show' => 1,
				'required' => 0,
			],
		];
	}
}
