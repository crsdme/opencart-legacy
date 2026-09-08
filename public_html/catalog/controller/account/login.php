<?php
class ControllerAccountLogin extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->model('account/customer');

		$account = new \Custom\Account($this->registry);

		if (!empty($this->request->get['token'])) {
			$this->customer->logout();
			$this->cart->clear();

			unset($this->session->data['order_id']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['shipping_address']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

			$customer_info = $this->model_account_customer->getCustomerByToken($this->request->get['token']);

			if ($customer_info && $this->customer->login($customer_info['email'], '', true)) {
				$account->afterLogin();
				$this->response->redirect($this->url->link('account/account', '', true));
			}
		}

		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->language('account/login');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$phone_auth = $account->isPhone();
		$account->addCatalogAssets();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate($account, $phone_auth)) {
			$this->response->redirect($account->redirectAfterLogin($this->request));
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home'),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_login'),
			'href' => $this->url->link('account/login', '', true),
		];

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['phone_auth'] = $phone_auth;
		$data['phone_prefix'] = $account->prefix();
		$data['action'] = $this->url->link('account/login', '', true);
		$data['send_code'] = $this->url->link('account/login/sendCode', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['forgotten'] = $this->url->link('account/forgotten', '', true);

		if (
			isset($this->request->post['redirect']) &&
			(strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false ||
				strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)
		) {
			$data['redirect'] = $this->request->post['redirect'];
		} elseif (isset($this->session->data['redirect'])) {
			$data['redirect'] = $this->session->data['redirect'];

			unset($this->session->data['redirect']);
		} else {
			$data['redirect'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['email'] = isset($this->request->post['email']) ? $this->request->post['email'] : '';
		$data['password'] = isset($this->request->post['password']) ? $this->request->post['password'] : '';
		$data['telephone'] = isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '';
		$data['code'] = isset($this->request->post['code']) ? $this->request->post['code'] : '';

		$data['view'] = 'account/login';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	public function sendCode()
	{
		$this->load->language('account/login');

		$json = [];
		$account = new \Custom\Account($this->registry);

		if (!$account->isPhone()) {
			$json['error'] = $this->language->get('error_login');
		} elseif ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error'] = $this->language->get('error_login');
		} else {
			$telephone = isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '';
			$result = $account->sendLoginCode($telephone);

			if (!empty($result['error'])) {
				$json['error'] = $this->otpError($result);
			} else {
				$json['success'] = $this->language->get('text_code_sent');
				$json['retry_after'] = isset($result['retry_after']) ? (int) $result['retry_after'] : 60;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate($account, $phone_auth)
	{
		if ($phone_auth) {
			$telephone = isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '';
			$code = isset($this->request->post['code']) ? $this->request->post['code'] : '';
			$result = $account->loginByPhone($telephone, $code);

			if (!empty($result['error'])) {
				$this->error['warning'] = $this->otpError($result);
			}

			return !$this->error;
		}

		$email = isset($this->request->post['email']) ? $this->request->post['email'] : '';
		$password = isset($this->request->post['password']) ? $this->request->post['password'] : '';

		$login_info = $this->model_account_customer->getLoginAttempts($email);

		if (
			$login_info &&
			$login_info['total'] >= $this->config->get('config_login_attempts') &&
			strtotime('-1 hour') < strtotime($login_info['date_modified'])
		) {
			$this->error['warning'] = $this->language->get('error_attempts');
		}

		$customer_info = $this->model_account_customer->getCustomerByEmail($email);

		if ($customer_info && !$customer_info['status']) {
			$this->error['warning'] = $this->language->get('error_approved');
		}

		if (!$this->error) {
			if (!$this->customer->login($email, $password)) {
				$this->error['warning'] = $this->language->get('error_login');

				$this->model_account_customer->addLoginAttempt($email);
			} else {
				$this->model_account_customer->deleteLoginAttempts($email);
				$account->afterLogin();
			}
		}

		return !$this->error;
	}

	private function otpError($result)
	{
		$key = 'error_' . (isset($result['error']) ? $result['error'] : 'login');

		if ($this->language->get($key) && $this->language->get($key) !== $key) {
			$text = $this->language->get($key);
		} else {
			$text = $this->language->get('error_login');
		}

		if (!empty($result['retry_after']) && $result['error'] === 'wait') {
			$text = sprintf($text, (int) $result['retry_after']);
		}

		return $text;
	}
}
