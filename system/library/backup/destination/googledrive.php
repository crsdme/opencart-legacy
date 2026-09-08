<?php

namespace Backup\Destination;

class GoogleDrive implements \Backup\Destination
{
	private $store;
	private $http;
	private $access_token = '';

	public function __construct(\Backup\Store $store)
	{
		$this->store = $store;
		$this->http = new \Backup\Http();
	}

	public function upload($local_path, $filename)
	{
		$token = $this->token();
		$folder = $this->folderId();
		$size = filesize($local_path);
		$meta = json_encode([
			'name' => $filename,
			'parents' => [$folder],
		]);

		$init = $this->http->request(
			'POST',
			'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true',
			$meta,
			[
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json; charset=UTF-8',
				'X-Upload-Content-Type: application/zip',
				'X-Upload-Content-Length: ' . $size,
			]
		);

		$location = isset($init['headers']['location']) ? $init['headers']['location'] : '';

		if ($location === '') {
			throw new \RuntimeException('Google Drive did not return an upload URL.');
		}

		$chunk = 8 * 1024 * 1024;
		$offset = 0;
		$handle = fopen($local_path, 'rb');
		$result_body = '';

		if (!$handle) {
			throw new \RuntimeException('Cannot read backup file for upload.');
		}

		while ($offset < $size) {
			$length = min($chunk, $size - $offset);
			$data = fread($handle, $length);

			if ($data === false || $data === '') {
				fclose($handle);
				throw new \RuntimeException('Short read while uploading to Google Drive.');
			}

			$end = $offset + strlen($data) - 1;

			$response = $this->http->request(
				'PUT',
				$location,
				$data,
				[
					'Authorization: Bearer ' . $token,
					'Content-Length: ' . strlen($data),
					'Content-Range: bytes ' . $offset . '-' . $end . '/' . $size,
				],
				true
			);

			if ($response['status'] >= 400) {
				fclose($handle);
				throw new \RuntimeException('Google Drive upload failed: HTTP ' . $response['status']);
			}

			$result_body = $response['body'];
			$offset = $end + 1;
		}

		fclose($handle);
		$decoded = json_decode($result_body, true);

		if (empty($decoded['id'])) {
			throw new \RuntimeException('Google Drive upload did not return a file id.');
		}

		return $decoded['id'];
	}

	public function purge($keep, $prefix)
	{
		$folder = $this->folderId();
		$q = sprintf(
			"'%s' in parents and name contains '%s' and trashed = false",
			str_replace("'", "\\'", $folder),
			str_replace("'", "\\'", $prefix)
		);

		$data = $this->http->json(
			'GET',
			'https://www.googleapis.com/drive/v3/files?q=' . rawurlencode($q) . '&orderBy=createdTime desc&pageSize=100&fields=files(id,name,createdTime)',
			null,
			['Authorization: Bearer ' . $this->token()]
		);

		$files = isset($data['files']) ? $data['files'] : [];
		$keep = max(0, (int) $keep);

		foreach (array_slice($files, $keep) as $file) {
			$this->delete($file['id']);
		}
	}

	public function delete($ref)
	{
		if ($ref === '') {
			return;
		}

		$this->http->request(
			'DELETE',
			'https://www.googleapis.com/drive/v3/files/' . rawurlencode($ref) . '?supportsAllDrives=true',
			null,
			['Authorization: Bearer ' . $this->token()],
			true
		);
	}

	public function test()
	{
		$data = $this->http->json(
			'GET',
			'https://www.googleapis.com/drive/v3/about?fields=user',
			null,
			['Authorization: Bearer ' . $this->token()]
		);

		$email = isset($data['user']['emailAddress']) ? $data['user']['emailAddress'] : 'Google Drive';

		return $email;
	}

	public function exchangeCode($code)
	{
		$result = $this->http->form('https://oauth2.googleapis.com/token', [
			'code' => $code,
			'client_id' => (string) $this->store->get('module_auto_backup_google_client_id', ''),
			'client_secret' => $this->store->getSecret('module_auto_backup_google_client_secret'),
			'redirect_uri' => $this->store->oauthRedirectUri(),
			'grant_type' => 'authorization_code',
		]);

		if (empty($result['refresh_token'])) {
			throw new \RuntimeException('Google did not return a refresh token. Disconnect the app in Google Account and connect again.');
		}

		$this->store->saveKey(
			'module_auto_backup_google_refresh_token',
			$this->store->encryptValue($result['refresh_token'])
		);

		return true;
	}

	private function token()
	{
		if ($this->access_token !== '') {
			return $this->access_token;
		}

		$refresh = $this->store->getSecret('module_auto_backup_google_refresh_token');

		if ($refresh === '') {
			throw new \RuntimeException('Google Drive is not connected.');
		}

		$result = $this->http->form('https://oauth2.googleapis.com/token', [
			'client_id' => (string) $this->store->get('module_auto_backup_google_client_id', ''),
			'client_secret' => $this->store->getSecret('module_auto_backup_google_client_secret'),
			'refresh_token' => $refresh,
			'grant_type' => 'refresh_token',
		]);

		if (empty($result['access_token'])) {
			throw new \RuntimeException('Cannot refresh Google Drive token.');
		}

		$this->access_token = $result['access_token'];

		return $this->access_token;
	}

	private function folderId()
	{
		$id = (string) $this->store->get('module_auto_backup_google_folder_id', '');

		if ($id !== '') {
			return $id;
		}

		$created = $this->http->json(
			'POST',
			'https://www.googleapis.com/drive/v3/files?supportsAllDrives=true',
			[
				'name' => 'OpenTail Backups',
				'mimeType' => 'application/vnd.google-apps.folder',
			],
			['Authorization: Bearer ' . $this->token()]
		);

		if (empty($created['id'])) {
			throw new \RuntimeException('Cannot create Google Drive folder.');
		}

		$this->store->saveKey('module_auto_backup_google_folder_id', $created['id']);

		return $created['id'];
	}
}
