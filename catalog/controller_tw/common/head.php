<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCommonHead extends Controller
{
    public function index($data)
    {
        // Analytics
        $this->load->model('setting/extension');

        $data['analytics'] = [];

        $analytics = $this->model_setting_extension->getExtensions('analytics');

        foreach ($analytics as $analytic) {
            if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
                $data['analytics'][] = $this->load->controller(
                    'extension/analytics/' . $analytic['code'],
                    $this->config->get('analytics_' . $analytic['code'] . '_status'),
                );
            }
        }

        if ($this->request->server['HTTPS']) {
            $server = $this->config->get('config_ssl');
        } else {
            $server = $this->config->get('config_url');
        }

        $host =
            isset($this->request->server['HTTPS']) &&
            ($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == '1')
                ? HTTPS_SERVER
                : HTTP_SERVER;
        if ($this->request->server['REQUEST_URI'] == '/') {
            $data['og_url'] = $this->url->link('common/home');
        } else {
            $data['og_url'] =
                $host .
                substr($this->request->server['REQUEST_URI'], 1, strlen($this->request->server['REQUEST_URI']) - 1);
        }

        $this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');

        $data['base'] = $server;
        $data['title'] = $this->document->getTitle();
        $data['description'] = $this->document->getDescription();
        $data['keywords'] = $this->document->getKeywords();
        $data['links'] = $this->document->getLinks();
        $data['robots'] = $this->document->getRobots();
        $data['styles'] = $this->document->getStyles();
        $data['scripts'] = $this->document->getScripts('header');
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');

        $data['name'] = $this->config->get('config_name');
        $data['theme'] = $this->request->cookie['theme'] ?? null;
        $data['route'] = $this->request->get['route'] ?? 'common/home';

        $data['microdata'] = $this->load->controller('common/microdata', $data);

        return $this->load->view('common/head', $data);
    }
}
