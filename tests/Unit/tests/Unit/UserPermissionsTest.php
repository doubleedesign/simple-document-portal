<?php
/** @noinspection PhpUnhandledExceptionInspection */
use Doubleedesign\SimpleDocumentPortal\Tests\Unit\WP_Roles_Mock;
use Doubleedesign\SimpleDocumentPortal\UserPermissions;
use function Brain\Monkey\Functions\{when};

$wpRolesMock = WP_Roles_Mock::create();

beforeEach(function() use ($wpRolesMock) {
    when('is_admin')->justReturn(true);
    when('wp_roles')->justReturn($wpRolesMock);
    when('get_users')->justReturn([]);
});

describe('capability mapping', function() {
    it('should map read_documents to read_private_posts', function() {
        $result = UserPermissions::get_mapped_built_in_capability('read_documents');
        expect($result)->toBe('read_private_posts');
    });

    it('should map edit_documents to edit_private_posts', function() {
        $result = UserPermissions::get_mapped_built_in_capability('edit_documents');
        expect($result)->toBe('edit_private_posts');
    });

    it('should map delete_documents to delete_private_posts', function() {
        $result = UserPermissions::get_mapped_built_in_capability('delete_documents');
        expect($result)->toBe('delete_private_posts');
    });

    it('should map publish_documents to publish_posts', function() {
        $result = UserPermissions::get_mapped_built_in_capability('publish_documents');
        expect($result)->toBe('publish_posts');
    });

    it('should map manage_documents_options to manage_options', function() {
        $result = UserPermissions::get_mapped_built_in_capability('manage_documents_options');
        expect($result)->toBe('manage_options');
    });
});

describe('updating roles', function() use ($wpRolesMock) {
    it('adds mapped capabilities to roles', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('add_cap');

        UserPermissions::map_permissions_to_existing_roles();

        // Just a simple cross-section, not every role/capability combination
        expect($spy->was_called_with('administrator', 'read_documents'))->toBeTrue()
            ->and($spy->was_called_with('administrator', 'manage_documents_options'))->toBeTrue()
            ->and($spy->was_called_with('administrator', 'publish_documents'))->toBeTrue()
            ->and($spy->was_called_with('editor', 'edit_documents'))->toBeTrue()
            ->and($spy->was_called_with('editor', 'publish_documents'))->toBeTrue()
            ->and($spy->was_called_with('portal_member', 'read_documents'))->toBeTrue();
    });

    it('does not incorrectly add capabilities to roles', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('add_cap');

        UserPermissions::map_permissions_to_existing_roles();

        // Just a simple cross-section, not every role/capability combination
        expect($spy->was_called_with('subscriber', 'read_documents'))->toBeFalse()
            ->and($spy->was_called_with('editor', 'manage_documents_options'))->toBeFalse()
            ->and($spy->was_called_with('portal_member', 'edit_documents'))->toBeFalse()
            ->and($spy->was_called_with('portal_member', 'publish_documents'))->toBeFalse()
            ->and($spy->was_called_with('portal_member', 'manage_documents_options'))->toBeFalse();
    });
});

describe('resetting capabilities', function() use ($wpRolesMock) {
    it('should remove capabilities for all roles', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('remove_cap');

        UserPermissions::reset_default_capabilities();

        // Just a simple cross-section, not every role/capability combination
        expect($spy->was_called_with('administrator', 'read_documents'))->toBeTrue()
            ->and($spy->was_called_with('editor', 'edit_documents'))->toBeTrue()
            ->and($spy->was_called_with('portal_member', 'read_documents'))->toBeTrue();
    });

    it('should not remove mapped built-in capabilities', function() {
        $wpRolesMock = WP_Roles_Mock::create();
        when('wp_roles')->justReturn($wpRolesMock);
        when('get_users')->justReturn([]);

        $spy = $wpRolesMock->spy_on_method('remove_cap');

        UserPermissions::reset_default_capabilities();

        // Just a simple cross-section, not every role/capability combination
        expect($spy->was_called_with('administrator', 'read_private_posts'))->toBeFalse()
            ->and($spy->was_called_with('editor', 'edit_private_posts'))->toBeFalse()
            ->and($spy->was_called_with('portal_member', 'read_private_posts'))->toBeFalse();
    });
});
