<?php
/**
 * This file is used when using PhpStorm's "Run with coverage" feature, which requires a root installation of Pest rather than using the one in the relevant test directory.
 */

// Get which directory this is running from
$cwd = getcwd();
$args = $_SERVER['argv'];
$path = array_pop($args);

if (str_contains($path, 'Unit')) {
    require_once 'tests/Unit/vendor/autoload.php';
    require_once 'tests/Unit/tests/Pest.php';
}
if (str_contains($path, 'Integration')) {
    require_once 'tests/Integration/tests/Pest.php';
}
if (str_contains($path, 'E2E')) {
    require_once 'tests/E2E/vendor/autoload.php';
    require_once 'tests/E2E/tests/Pest.php';
}
