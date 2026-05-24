<?php

	// 链接必要模块
	require_once __DIR__.'/conn.php';
	require_once __DIR__.'/functions.php';
	require_once __DIR__.'/functions_else.php';

	$uid = 109;
	$size = "small";
	to_avif(__DIR__."/data/forums/3/data3/{$uid}_{$size}.jpg");