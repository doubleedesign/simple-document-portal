<?php
/** @noinspection PhpUnhandledExceptionInspection */
use Doubleedesign\SimpleDocumentPortal\FileHandler;
use Doubleedesign\SimpleDocumentPortal\Tests\Unit\{WPDB_Mock, WP_Query_Mock};

beforeEach(function() {
    $wpdb = new WPDB_Mock();
    $wpdb->stub('get_col')
        ->with_sql("SELECT meta_value FROM wp_postmeta WHERE meta_key = 'protected_document_file'")
        ->will_return([17, 30, 27]);
});

it('filters protected documents out of queries for attachments', function() {
    $queryObj = WP_Query_Mock::create();
    $queryObj->add_method('get')->when_called->with('post_type')->will_return('attachment');
    $spy = $queryObj->spy_on_method('set');

    $instance = new FileHandler();
    $instance->filter_document_files_from_media_library($queryObj);

    // Debug what it was called with
    // dump($spy->get_call(0));

    expect($spy->was_called_with('post__not_in', [17, 30, 27]))->toBeTrue();
});

it('does not filter queries for non-attachment post types', function() {
    $queryObj = WP_Query_Mock::create();
    $queryObj->add_method('get')->when_called->with('post_type')->will_return('page');
    $spy = $queryObj->spy_on_method('set');

    $instance = new FileHandler();
    $instance->filter_document_files_from_media_library($queryObj);

    expect($spy->was_called())->toBeFalse();
});
