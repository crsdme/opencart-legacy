<?php
class ControllerCommonComponents extends Controller {
    public function index(): void {
        $components_dir = DIR_TEMPLATE . 'tailwind/components/';
        $files = glob($components_dir . '*.twig');

        $data['components'] = array_map(function($file) {
            return basename($file, '.twig');
        }, $files);

        $this->response->setOutput($this->load->view('common/components', $data));
    }
}
