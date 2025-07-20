<?php

uses()->beforeEach(function() {
    $wpPath = getenv('USERPROFILE') . getenv('APP_DIR');
    putenv("WP_PATH=$wpPath");

	if (!getenv('WP_PATH')) {
		throw new Exception('WP_PATH environment variable is not set. Please set it to the path of your WordPress installation.');
	}

	require_once getenv('WP_PATH') . '/wp-load.php';
})->in('Integration');
