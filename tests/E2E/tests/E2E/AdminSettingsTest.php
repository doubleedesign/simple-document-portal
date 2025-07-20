<?php
test('can navigate to settings page', function() {
    $this->browse(function($browser) {
        $browser->visit($_ENV['APP_URL'] . '/wp-login.php')
            ->type('log', 'doubleedesign')
            ->type('pwd', 'doubleedesign')
            ->press('Log In')
            ->assertSee('Dashboard');

        // Hover over 'Documents' in the admin menu
        $browser->pause(300)
            ->mouseover('#menu-posts-document')
            ->pause(100)
            ->click('a[href="edit.php?post_type=document&page=settings"]')
            ->pause(100)
            ->assertSee('Document Portal Settings');
    });
});
