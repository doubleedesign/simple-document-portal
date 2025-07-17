<?php

// Use the custom DuskTestCase for tests in the E2E directory
use Doubleedesign\SimpleDocumentPortal\Tests\E2E\DuskTestCase;

uses(DuskTestCase::class)->in('E2E');

// Setup/checks to run before browser tests
// Note: Only beforeEach() is supported in Pest.php, not beforeAll(),
// so checks here should be kept to a minimum so we don't slow things down unnecessarily.
// Consider whether the thing you want to check can be put in /Browser/StartTest.php instead.
uses()->beforeEach(function() {
    // Bail if ChromeDriver is not running, and provide instructions to start it
    $port = $_ENV['DUSK_WEBDRIVER_PORT'];
    $connection = @fsockopen('localhost', $port, $errno, $errstr, 1);
    if ($connection === false) {
        error_log("ChromeDriver is not running on port $port. Please start it before running the tests.");
        error_log("Use the command: chromedriver --port=$port");
        exit(1);
    }
})->in('E2E');
