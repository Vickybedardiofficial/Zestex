<?php

return [
	'group' => [
		'avatar' => 'assets/avatars/default-avatar.png',
		'invite_expire_days' => 7
	],
	'message' => [
		'validation' => [
			'content' => [
				'min' => 1,
				'max' => 2200
			],
		]
	],
	'attachments' => [
		'max_count' => 5,
		'max_size_mb' => 20,
		'types' => [
			'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
			'video' => ['mp4', 'webm'],
			'audio' => ['mp3', 'wav', 'ogg'],
			'document' => ['pdf', 'docx', 'xlsx', 'pptx', 'txt'],
			'file' => []
		],
		'mimes' => [
			'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
			'video' => ['video/mp4', 'video/webm'],
			'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
			'document' => [
				'application/pdf',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'text/plain'
			],
			'file' => [
				'application/zip',
				'application/x-7z-compressed',
				'application/x-rar-compressed',
				'application/x-tar',
				'application/gzip',
				'application/octet-stream'
			]
		]
	],
	'colors' => [
		'#C7508B',
		'#D67722',
		'#CC5049',
		'#309eba',
		'#40a920',
		'#955cdb'
	],
	'sounds' => [
		'active_chat_message_received' => 'assets/sounds/chats/active-chat-message-received.mp3',
		'background_chat_message_received' => 'assets/sounds/chats/background-chat-message-received.mp3',
		'chat_message_sent' => 'assets/sounds/chats/chat-message-sent.mp3',
	]
];
