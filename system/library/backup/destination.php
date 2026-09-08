<?php

namespace Backup;

interface Destination
{
	/**
	 * @return string Remote reference (file id, path, or local filename)
	 */
	public function upload($local_path, $filename);

	/**
	 * Keep the newest $keep files whose names start with $prefix.
	 */
	public function purge($keep, $prefix);

	/**
	 * @param string $ref Remote reference from upload()
	 */
	public function delete($ref);

	/**
	 * @return string Short status for the admin UI
	 */
	public function test();
}
