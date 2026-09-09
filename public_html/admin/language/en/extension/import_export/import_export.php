<?php
$_['heading_title'] = 'Import / Export';

$_['text_home'] = 'Home';
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Settings saved';
$_['text_edit'] = 'Catalog import / export';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_all'] = 'All';
$_['text_or'] = 'Or paste JSON';
$_['text_recent'] = 'Recent jobs';
$_['text_no_results'] = 'No jobs yet.';
$_['text_confirm'] = 'Are you sure?';
$_['text_deleted'] = 'Job deleted.';
$_['text_imported'] = 'Import finished. Created: %s, updated: %s, skipped: %s, errors: %s';
$_['text_pagination'] = 'Showing %d–%d of %d (%d pages)';

$_['text_tab_dashboard'] = 'Settings';
$_['text_tab_template'] = 'Templates';
$_['text_tab_export'] = 'Export';
$_['text_tab_import'] = 'Import';
$_['text_tab_job'] = 'History';

$_['text_template'] = 'Download a template';
$_['text_export'] = 'Export catalog';
$_['text_import'] = 'Import catalog';
$_['text_csv_hint'] = 'CSV needs an entity type. JSON can be a single entity or a full catalog bundle.';

$_['text_entity_auto'] = 'Auto (JSON)';
$_['text_entity_bundle'] = 'Full catalog (bundle)';
$_['text_entity_product'] = 'Products';
$_['text_entity_category'] = 'Categories';
$_['text_entity_manufacturer'] = 'Manufacturers';
$_['text_entity_attribute'] = 'Attributes';
$_['text_entity_attribute_group'] = 'Attribute groups';

$_['text_key_sku'] = 'SKU';
$_['text_key_model'] = 'Model';
$_['text_missing_create'] = 'Create missing references';
$_['text_missing_error'] = 'Stop with an error';
$_['text_missing_skip'] = 'Skip the row';

$_['text_create'] = 'Create';
$_['text_update'] = 'Update';
$_['text_skip'] = 'Skip';
$_['text_error'] = 'Error';
$_['text_total'] = 'Total';

$_['text_action_import'] = 'Import';
$_['text_action_export'] = 'Export';
$_['text_status_success'] = 'OK';
$_['text_status_error'] = 'Error';

$_['entry_status'] = 'Status';
$_['entry_product_key'] = 'Product identity';
$_['entry_on_missing'] = 'Missing categories / attributes / brands';
$_['entry_download_images'] = 'Download remote images';
$_['entry_delete_data'] = 'Delete job history on uninstall';
$_['entry_entity'] = 'Entity';
$_['entry_format'] = 'Format';
$_['entry_file'] = 'File';
$_['entry_category'] = 'Category';
$_['entry_manufacturer'] = 'Manufacturer';

$_['help_status'] = 'Off only hides the idea of running this from cron later. Admin pages still work.';
$_['help_product_key'] = 'When product_id is omitted: match by SKU, or by model if SKU is empty. product_id 0 always creates. Negative ids (e.g. -1) are aliases inside this file only.';
$_['help_on_missing'] = 'When a product points at a category, attribute or manufacturer that does not exist yet.';
$_['help_download_images'] = 'http(s) image URLs are saved under image/catalog/import/. Local paths are unchanged. Failed downloads skip that image. JPEG, PNG, GIF, WebP, max 8 MB.';
$_['help_delete_data'] = 'If enabled, uninstall drops the job history table. Catalog data is never deleted.';
$_['help_template'] = 'Empty CSV/JSON from the live schema. Prefer an export so shop IDs are real. New rows use 0 or negative aliases (-1, -2) in a JSON bundle so they can link before the shop assigns ids.';
$_['help_export'] = 'Product filters apply to products. Bundle export dumps every supported entity.';
$_['help_import'] = 'Preview first. Live positive ids update that row. 0 = create. Negative ids (-1, -2) create and let other rows in this file link to them (JSON bundle for category + product). Images: local image/catalog path, or http(s) URL to download.';

$_['column_id'] = 'ID';
$_['column_date'] = 'Date';
$_['column_action'] = 'Action';
$_['column_entity'] = 'Entity';
$_['column_format'] = 'Format';
$_['column_file'] = 'File';
$_['column_status'] = 'Status';
$_['column_created'] = 'Created';
$_['column_updated'] = 'Updated';
$_['column_skipped'] = 'Skipped';
$_['column_errors'] = 'Errors';
$_['column_message'] = 'Log';
$_['column_row'] = 'Row';
$_['column_identity'] = 'Identity';

$_['button_save'] = 'Save';
$_['button_cancel'] = 'Cancel';
$_['button_download'] = 'Download';
$_['button_preview'] = 'Preview';
$_['button_import'] = 'Import';
$_['button_delete'] = 'Delete';

$_['error_permission'] = 'You do not have permission to modify Import / Export.';
$_['error_file'] = 'Upload a CSV/JSON file or paste JSON.';
