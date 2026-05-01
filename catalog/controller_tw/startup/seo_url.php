<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerStartupSeoUrl extends Controller
{
  private $custom_router;

  public function __construct($registry)
  {
    parent::__construct($registry);
    $this->custom_router = new \Custom\Router($registry);
  }

  public function index()
  {
    if ($this->config->get('config_seo_url'))
      $this->url->addRewrite($this);

    if (isset($this->request->get['_route_'])) {
      $parts = explode('/', $this->request->get['_route_']);

      $this->custom_router->prepareRoute($parts);
    }
  }

  public function rewrite($link)
  {
    return $this->custom_router->rewrite($link);
  }
}
