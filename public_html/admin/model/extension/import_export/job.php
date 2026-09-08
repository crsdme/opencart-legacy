<?php

class ModelExtensionImportExportJob extends Model
{
	public function history()
	{
		return new \import_export\Job($this->db);
	}
}
