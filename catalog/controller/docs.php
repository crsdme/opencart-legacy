<?php
class ControllerDocs extends Controller
{
	public function index()
	{
		return $this->load->controller('docs/index');
	}
}
