<?php

class ModelAccountPhone extends Model
{
	public function getCustomerByTelephone($telephone)
	{
		$prefix = \Custom\Phone::prefix($this->config);
		$normalized = \Custom\Phone::normalize($telephone, $prefix);

		if ($normalized === '') {
			return [];
		}

		$tail = substr($normalized, -9);

		$query = $this->db->query(
			'SELECT * FROM `' .
				DB_PREFIX .
				"customer` WHERE telephone != '' AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telephone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') LIKE '%" .
				$this->db->escape($tail) .
				"'",
		);

		foreach ($query->rows as $row) {
			if (\Custom\Phone::normalize($row['telephone'], $prefix) === $normalized) {
				return $row;
			}
		}

		return [];
	}

	public function addCustomerByTelephone($telephone)
	{
		$prefix = \Custom\Phone::prefix($this->config);
		$phone = \Custom\Phone::normalize($telephone, $prefix);

		$this->load->model('account/customer');

		return $this->model_account_customer->addCustomer([
			'firstname' => '',
			'lastname' => '',
			'email' => $phone . '@sms.invalid',
			'telephone' => $phone,
			'password' => token(16),
			'newsletter' => 0,
		]);
	}
}
