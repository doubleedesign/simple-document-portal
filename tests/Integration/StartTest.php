<?php

// This file contains some initial tests to ensure the REST API setup is ready.
// They are not in Pest.php because we only want to run them once, and only beforeEach() works there.
// The PhpStorm Run config "All REST API Tests" is set up to run this file first,
// but it will not be automatically run when running individual tests or files from the IDE or the command line
// unless you explicitly configure them to do so.

test('REST API test user exists', function() {
    $wpPath = $_ENV['WP_PATH']; // Note: this is set dynamically in Pest.php beforeEach() for REST tests
    $result = shell_exec("wp user get rest-api-test-account --field=ID --path=$wpPath");
    if (str_contains($result, 'Error')) {
        error_log('REST API test user does not exist. Please create a user with the username "rest-api-test-account" before running the tests.');
        exit(1);
    }

    expect($result)->toBeNumeric()->toBeGreaterThan(0);
});

test('REST API application password is available', function() {
    $password = $_ENV['REST_API_TEST_APPLICATION_PASSWORD'];
    if (empty($password)) {
        error_log("REST API test application password is not set, or not available where expected.\n Please set the environment variable REST_API_TEST_APPLICATION_PASSWORD.");
        exit(1);
    }

    expect($password)->toBeString()->not->toBeEmpty();
});
