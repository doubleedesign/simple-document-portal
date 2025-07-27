<?php
use Doubleedesign\Comet\Core\{ResponsivePanel, ResponsivePanels, PreprocessedHTML};

get_header();

if (!is_user_logged_in()) {
    wp_login_form();
}

// Get all Folder taxonomy terms
$folders = get_terms([
    'taxonomy'   => 'folder',
    'hide_empty' => false,
]);

$panels = new ResponsivePanels([], [new ResponsivePanel(['title' => "Test panel"], [new PreprocessedHtml('<h1>Documents</h1>')])]);
$panels->render();

get_footer();
