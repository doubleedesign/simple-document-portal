<?php
test('logged-out user sees login form, does not see documents', function() {
	$this->browse(function($browser) {
		$browser->visit($_ENV['APP_URL'] . '/portal')
			->assertVisible('#loginform')
			->assertMissing('.responsive-panels');
	});
});

test('logged-in subscriber sees no permission message, does not see documents', function() {
	$this->browse(function($browser) {
		$browser->visit($_ENV['APP_URL'] . '/portal')
			->type('log', 'test-subscriber')
			->type('pwd', 'test-subscriber')
			->press('Log In');

		$browser->pause(300)
			->assertSee('No access')
			->assertMissing('.responsive-panels');
	});
});


test('logged-in portal member sees documents', function() {
	$this->browse(function($browser) {
		$browser->visit($_ENV['APP_URL'] . '/portal')
			->type('log', 'test-member')
			->type('pwd', 'test-member')
			->press('Log In');

		$browser->pause(300)
			->assertVisible('.responsive-panels');
	});
});
