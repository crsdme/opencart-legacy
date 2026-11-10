<?php
class ModelCatalogRecentlyViewed extends Model
{
	private const SESSION_KEY = 'recently_viewed';
	private const MAX_IDS = 20;

	public function isRobot(): bool
	{
		$user_agent = $this->request->server['HTTP_USER_AGENT'] ?? '';

		if ($user_agent === '') {
			return false;
		}

		$robots = (string) $this->config->get('config_robots');

		if ($robots === '') {
			return false;
		}

		$user_agent = utf8_strtolower($user_agent);
		$robots = str_replace(["\r\n", "\r"], "\n", utf8_strtolower($robots));

		foreach (explode("\n", $robots) as $robot) {
			$robot = trim($robot);

			if ($robot !== '' && utf8_strpos($user_agent, $robot) !== false) {
				return true;
			}
		}

		return false;
	}

	public function add(int $product_id): void
	{
		$product_id = (int) $product_id;

		if ($product_id < 1 || $this->isRobot()) {
			return;
		}

		$ids = $this->normalizeIds($this->session->data[self::SESSION_KEY] ?? []);
		$ids = array_values(array_filter($ids, static function ($id) use ($product_id) {
			return $id !== $product_id;
		}));

		array_unshift($ids, $product_id);

		if (count($ids) > self::MAX_IDS) {
			$ids = array_slice($ids, 0, self::MAX_IDS);
		}

		$this->session->data[self::SESSION_KEY] = $ids;
	}

	public function getIds(int $limit, int $exclude_id = 0): array
	{
		$limit = max(0, $limit);
		$exclude_id = (int) $exclude_id;
		$ids = $this->normalizeIds($this->session->data[self::SESSION_KEY] ?? []);

		if ($exclude_id > 0) {
			$ids = array_values(array_filter($ids, static function ($id) use ($exclude_id) {
				return $id !== $exclude_id;
			}));
		}

		if ($limit > 0) {
			$ids = array_slice($ids, 0, $limit);
		}

		return $ids;
	}

	private function normalizeIds($ids): array
	{
		if (!is_array($ids)) {
			return [];
		}

		$normalized = [];

		foreach ($ids as $id) {
			$id = (int) $id;

			if ($id > 0 && !in_array($id, $normalized, true)) {
				$normalized[] = $id;
			}
		}

		return $normalized;
	}
}
