<?php

return [
	'default_language' => 'en',
	'languages' => [
		'en' => 'EN',
		'ru' => 'RU',
	],
	'groups' => [
		'general' => [
			'en' => 'Guides',
			'ru' => 'Общее',
		],
		'technical' => [
			'en' => 'Technical',
			'ru' => 'Техническое',
		],
	],
	'docs' => [
		['id' => 'getting-started', 'group' => 'general', 'file' => 'getting-started.md'],
		[
			'id' => 'overview',
			'group' => 'general',
			'file' => 'documentation.md',
			'title' => ['en' => 'Overview', 'ru' => 'Обзор'],
		],
		['id' => 'roadmap', 'group' => 'general', 'file' => 'roadmap.md'],
		['id' => 'faq', 'group' => 'technical', 'file' => 'faq.md'],
		['id' => 'redirect_manager', 'group' => 'technical', 'file' => 'redirect_manager.md'],
		['id' => 'import_export', 'group' => 'technical', 'file' => 'import_export.md'],
		['id' => 'backup', 'group' => 'technical', 'file' => 'backup.md'],
		['id' => 'sms', 'group' => 'technical', 'file' => 'sms.md'],
		['id' => 'language-flow-session-config', 'group' => 'technical', 'file' => 'language-flow-session-config.md'],
	],
];
