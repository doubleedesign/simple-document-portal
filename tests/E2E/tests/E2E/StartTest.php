<?php
use Doubleedesign\SimpleDocumentPortal\Tests\E2E\DuskTestCase;

// This file contains some initial tests to ensure the browser testing setup is working correctly.
// They are not in Pest.php because we only want to run them once, and only beforeEach() works there.
// The PhpStorm Run config "All E2E Tests" is set up to run this file first,
// but it will not be automatically run when running individual tests or files from the IDE or the command line
// unless you explicitly configure them to do so.

it('uses the correct browser test base class', function() {
    expect($this)->toBeInstanceOf(DuskTestCase::class);
});

it('can visit the homepage', function() {
    $this->browse(function($browser) {
        $browser->visit($_ENV['APP_URL'])
            ->assertSee('Hello world!');
    });
});
