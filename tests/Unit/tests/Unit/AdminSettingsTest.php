<?php
/** @noinspection PhpUnhandledExceptionInspection */
use Doubleedesign\SimpleDocumentPortal\{AdminSettings, UserPermissions};
use Doubleedesign\SimpleDocumentPortal\Tests\Unit\WP_Roles_Mock;
use function Brain\Monkey\Functions\{when};
use function Patchwork\{redefine};
use function Spies\{get_spy_for,match_array};

describe('Setup', function() {
    beforeEach(function() {
        $wpRolesMock = WP_Roles_Mock::create();
        when('wp_roles')->justReturn($wpRolesMock);
        when('get_users')->justReturn([]);
        when('wp_get_current_user')->justReturn((object)[
            'ID'    => 1,
            'roles' => ['administrator']
        ]);
    });

    it('creates the admin settings screen', function() {
        $spy = get_spy_for('acf_add_options_page');
        new AdminSettings();

        do_action('acf/init');

        expect($spy->was_called_with([
            'page_title'  => __('Document Portal Settings', 'simple-document-portal'),
            'menu_title'  => __('Portal Settings', 'simple-document-portal'),
            'parent_slug' => 'edit.php?post_type=document',
            'menu_slug'   => 'settings',
            'capability'  => 'manage_documents_options',
            'redirect'    => false,
        ]))->toBeTrue();
    });

    it('adds the general settings fields', function() {
        $spy = get_spy_for('acf_add_local_field_group');
        new AdminSettings();

        do_action('acf/include_fields');

        expect($spy->was_called_with(match_array([
            'key' => 'group_simple-document-portal__general-settings',
        ])))->toBeTrue();
    });

    it('adds the messages settings fields', function() {
        $spy = get_spy_for('acf_add_local_field_group');
        new AdminSettings();

        do_action('acf/include_fields');

        expect($spy->was_called_with(match_array([
            'key' => 'group_simple-document-portal__messages-settings',
        ])))->toBeTrue();
    });

    it('adds the access settings fields', function() {
        $spy = get_spy_for('acf_add_local_field_group');
        new AdminSettings();

        do_action('acf/include_fields');

        expect($spy->was_called_with(match_array([
            'key' => 'group_simple-document-portal__document-access-settings',
        ])))->toBeTrue();
    });
});

describe('Access settings', function() {
    $wpRolesMock = WP_Roles_Mock::create();

    beforeEach(function() use ($wpRolesMock) {
        when('is_admin')->justReturn(true);
        redefine([UserPermissions::class, 'get_roles_currently_in_use'], static function() {
            return ['administrator', 'editor', 'contributor', 'subscriber', 'portal_member'];
        });
        when('wp_roles')->justReturn($wpRolesMock);
        when('get_users')->justReturn([]);
        when('wp_get_current_user')->justReturn((object)[
            'ID'    => 1,
            'roles' => ['administrator']
        ]);
        when('delete_option')->justReturn(true);
    });

    afterEach(function() {
        Mockery::close();
        $_POST = [];
    });

    it('adds capabilities to ticked roles', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('add_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_edit_documents' => ['contributor']
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('contributor', 'edit_documents'))->toBeTrue();
    });

    it('handles multiple fields with different roles ticked for each', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('add_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_read_documents'           => ['contributor', 'subscriber'],
                'field_document_permission_edit_documents'           => ['contributor'],
                'field_document_permission_manage_documents_options' => ['contributor'],
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_times(4))
            ->and($spy->was_called_with('contributor', 'read_documents'))->toBeTrue()
            ->and($spy->was_called_with('subscriber', 'read_documents'))->toBeTrue()
            ->and($spy->was_called_with('contributor', 'edit_documents'))->toBeTrue()
            ->and($spy->was_called_with('subscriber', 'edit_documents'))->toBeFalse()
            ->and($spy->was_called_with('contributor', 'manage_documents_options'))->toBeTrue()
            ->and($spy->was_called_with('subscriber', 'manage_documents_options'))->toBeFalse();

    });

    it('removes a capability from unticked roles', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('remove_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_read_documents' => ['contributor']
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('subscriber', 'read_documents'))->toBeTrue()
            ->and($spy->was_called_with('contributor', 'read_documents'))->toBeFalse();
    });

    it('removes a capability from all editable roles if nothing is ticked', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('remove_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_read_documents' => ''
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('contributor', 'read_documents'))->toBeTrue()
            ->and($spy->was_called_with('subscriber', 'read_documents'))->toBeTrue();
    });

    it('does not modify roles that should not have a capability removed if nothing is ticked', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('remove_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_read_documents' => '',
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('administrator', 'read_documents'))->toBeFalse()
            ->and($spy->was_called_with('editor', 'read_documents'))->toBeFalse();
    });

    it('does not modify roles that should not have a capability removed if other roles are ticked', function() use ($wpRolesMock) {
        $spy = $wpRolesMock->spy_on_method('remove_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_read_documents' => ['administrator', 'subscriber']
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('administrator', 'read_documents'))->toBeFalse();
    });

    it('does not allow a user to remove a capability from their own role', function() use ($wpRolesMock) {
        when('wp_get_current_user')->justReturn((object)[
            'ID'    => 2,
            'roles' => ['editor']
        ]);
        $spy = $wpRolesMock->spy_on_method('remove_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_read_documents' => ['editor']
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('editor', 'read_documents'))->toBeFalse();
    });

    it('does not allow a user to add a capability to their own role', function() use ($wpRolesMock) {
        // Let's assume for this test that contributors have been granted manage_documents_options capability so could actually attempt this
        when('wp_get_current_user')->justReturn((object)[
            'ID'    => 1,
            'roles' => ['contributor']
        ]);

        $spy = $wpRolesMock->spy_on_method('add_cap');

        $_POST = array(
            'acf' => [
                'field_document_permission_publish_documents' => ['contributor']
            ]
        );

        $instance = new AdminSettings();
        $instance->save_access_settings('options');

        expect($spy->was_called_with('contributor', 'publish_documents'))->toBeFalse();
    });

});
