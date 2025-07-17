<?php

uses()->beforeEach(function() {
    // Dynamically set an env variable for the WordPress install path
    $wpPath = getenv('USERPROFILE') . $_ENV['APP_DIR'];
    $_ENV['WP_PATH'] = $wpPath;
})->in('Integration');

// E2E tests have their own Pest instance and hence Pest.php, to ensure that the E2E dependencies are not loaded for other test types.
// any ->in('E2E') in this file will have no effect.
