<?php

return [
	'routes' => [
		'default:avatar_service' => [
			'path' => '/avatar_service/{md5}',
			'controller' => \ColdTrick\AvatarService\Controllers\AvatarService::class,
			'walled' => false,
		],
	],
];
