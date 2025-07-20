<?php
/** @noinspection PhpUnhandledExceptionInspection */
use Doubleedesign\SimpleDocumentPortal\TemplateHandler;
use function Brain\Monkey\Functions\{when};
use function Spies\{mock_object, expect_spy};

beforeEach(function() {
    // Defaults
    when('is_tax')->justReturn(false);
    when('is_singular')->justReturn(false);
    when('is_post_type_archive')->justReturn(false);
});

it('should load the folder taxonomy template', function() {
    when('is_tax')->alias(function($taxonomy_name) {
        return $taxonomy_name === 'folder';
    });

    $instance = new TemplateHandler();
    $mock = mock_object($instance);
    $spy = $mock->spy_on_method('load_document_templates');

    $result = apply_filters('template_include', 'dummy-default.php', $spy);

    expect_spy($spy)->to_have_been_called();
    expect($result)->toBe('/templates/taxonomy-folder.php');
});

it('does not override the template for other taxonomies', function() {
    when('is_tax')->alias(function($taxonomy_name) {
        return $taxonomy_name === 'category';
    });

    $instance = new TemplateHandler();
    $mock = mock_object($instance);
    $spy = $mock->spy_on_method('load_document_templates');

    $result = apply_filters('template_include', 'dummy-default.php', $spy);

    expect_spy($spy)->to_have_been_called();
    expect($result)->toBe('dummy-default.php');
});

it('should load the document post type archive template', function() {
    when('is_post_type_archive')->alias(function($post_type) {
        return $post_type === 'document';
    });

    $instance = new TemplateHandler();
    $mock = mock_object($instance);
    $spy = $mock->spy_on_method('load_document_templates');

    $result = apply_filters('template_include', 'dummy-default.php', $spy);

    expect_spy($spy)->to_have_been_called();
    expect($result)->toBe('/templates/archive-document.php');
});

it('does not override the template for other post type archives', function() {
    when('is_post_type_archive')->alias(function($post_type) {
        return $post_type === 'event';
    });

    $instance = new TemplateHandler();
    $mock = mock_object($instance);
    $spy = $mock->spy_on_method('load_document_templates');

    $result = apply_filters('template_include', 'dummy-default.php', $spy);

    expect_spy($spy)->to_have_been_called();
    expect($result)->toBe('dummy-default.php');
});

it('should load the document single document template', function() {
    when('is_singular')->alias(function($post_type) {
        return $post_type === 'document';
    });

    $instance = new TemplateHandler();
    $mock = mock_object($instance);
    $spy = $mock->spy_on_method('load_document_templates');

    $result = apply_filters('template_include', 'dummy-default.php', $spy);

    expect_spy($spy)->to_have_been_called();
    expect($result)->toBe('/templates/single-document.php');
});

it('does not override the template for other single posts', function() {
    when('is_singular')->alias(function($post_type) {
        return $post_type === 'event';
    });

    $instance = new TemplateHandler();
    $mock = mock_object($instance);
    $spy = $mock->spy_on_method('load_document_templates');

    $result = apply_filters('template_include', 'dummy-default.php', $spy);

    expect_spy($spy)->to_have_been_called();
    expect($result)->toBe('dummy-default.php');
});
