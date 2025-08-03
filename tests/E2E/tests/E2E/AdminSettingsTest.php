<?php
test('admin user can navigate to settings page from the main menu', function() {
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

test('admin user can navigate to settings page from the plugins page', function() {
    $this->browse(function($browser) {
        $browser->visit($_ENV['APP_URL'] . '/wp-login.php')
            ->type('log', 'doubleedesign')
            ->type('pwd', 'doubleedesign')
            ->press('Log In')
            ->assertSee('Dashboard');

        // Navigate to Plugins page
        $browser->visit($_ENV['APP_URL'] . '/wp-admin/plugins.php')
            ->assertVisible('tr[data-slug="simple-document-portal"] .row-actions .settings a')
            ->click('tr[data-slug="simple-document-portal"] .row-actions .settings a')
            ->pause(100)
            ->assertSee('Document Portal Settings');
    });
});
