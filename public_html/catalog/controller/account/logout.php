<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountLogout extends Controller
{
	public function index()
	{
		if ($this->customer->isLogged()) {
			$this->customer->logout();

			unset($this->session->data['shipping_address']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

			$this->response->redirect($this->url->link('account/logout', '', true));
		}

		$this->load->language('account/logout');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

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
			'text' => $this->language->get('text_logout'),
			'href' => $this->url->link('account/logout', '', true),
		];

		$data['continue'] = $this->url->link('common/home');
		$data['login'] = $this->url->link('account/login', '', true);
		$data['view'] = 'account/logout';

		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
