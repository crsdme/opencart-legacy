<?php
class ControllerDocsAsset extends Controller
{
	public function index()
	{
		$path = isset($this->request->get['path']) ? (string) $this->request->get['path'] : '';
		$file = \Custom\Docs::mediaFile($path);

		if ($file === '') {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
			$this->response->setOutput('');
			return;
		}

		$mime = $this->mime($file);

		$this->response->addHeader('Content-Type: ' . $mime);
		$this->response->addHeader('Cache-Control: public, max-age=86400');
		$this->response->setOutput((string) file_get_contents($file));
	}

	private function mime($file)
	{
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		$map = [
			'png' => 'image/png',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'svg' => 'image/svg+xml',
			'avif' => 'image/avif',
		];

		return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
	}
}
