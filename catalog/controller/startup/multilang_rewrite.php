<?php
class ControllerStartupMultilangRewrite extends Controller
{
	private $custom_router;

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->custom_router = new \Custom\Router($registry);
	}

	public function index()
	{
		if (defined('DIR_APPLICATION') && strpos(DIR_APPLICATION, 'admin') !== false) {
			return;
		}

		$this->url->addRewrite($this);

		// Validate canonical URL after all rewrites are registered.
		$this->custom_router->validate();
	}

	// ADDING LANGUAGE PREFIX TO THIS->URL
	public function rewrite($link)
	{
		$code = $this->config->get('config_language');
		$is_main = $code === $this->config->get('config_language_main');
		$parsed = parse_url(str_replace('&amp;', '&', $link));
		$path = isset($parsed['path']) ? trim($parsed['path'], '/') : '';
		$query = isset($parsed['query']) ? $parsed['query'] : '';
		$base =
			(isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '') .
			(isset($parsed['host']) ? $parsed['host'] : '') .
			(isset($parsed['port']) ? ':' . $parsed['port'] : '');

		if ($is_main) {
			if (($path === '' || $path === 'index.php') && $query === 'route=common/home') {
				return $base . '/';
			}

			return $link;
		}

		$path_raw = isset($parsed['path']) ? $parsed['path'] : '';
		$query_suffix = $query !== '' ? '?' . $query : '';
		$frag = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

		// Keep unre-written index.php?route=… flat. Language is session/cookie; a /{code}/index.php
		// prefix makes SEO treat the request as a path and can overwrite the route (e.g. reviews).
		$is_index_php = $path === 'index.php' || substr($path, -10) === '/index.php';
		if ($is_index_php && $query !== '' && preg_match('/(?:^|&)route=/', $query)) {
			return $link;
		}

		$this->load->model('localisation/language');

		$codes = array_keys($this->model_localisation_language->getLanguages());
		$segments = $path === '' ? [] : explode('/', $path);

		// SeoPro stores common/home as the language code (en, ru). That URL is already the prefix.
		if (isset($segments[0]) && in_array(strtolower($segments[0]), $codes, true)) {
			if (count($segments) === 1 && substr($path_raw, -1) !== '/') {
				return $base . '/' . $segments[0] . '/' . $query_suffix . $frag;
			}

			return $link;
		}

		$is_home = ($path === '' || $path === 'index.php') && ($query === '' || $query === 'route=common/home');

		if ($is_home) {
			$path = '';
			$query_suffix = '';
		}

		$new_path = '/' . $code . ($path !== '' ? '/' . $path : '');

		// Language home must keep a trailing slash: .htaccess redirects /en → /en/.
		if ($path === '' || substr($path_raw, -1) === '/') {
			$new_path .= '/';
		}

		return $base . $new_path . $query_suffix . $frag;
	}
}
