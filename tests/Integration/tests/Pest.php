<?php

uses()->beforeEach(function() {
    // Dynamically set an env variable for the WordPress install path
    $wpPath = getenv('USERPROFILE') . $_ENV['APP_DIR'];
    $_ENV['WP_PATH'] = $wpPath;
})->in('Integration');
