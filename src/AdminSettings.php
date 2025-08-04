<?php

namespace Doubleedesign\SimpleDocumentPortal;

class AdminSettings {

    public function __construct() {
        add_action('acf/init', [$this, 'create_admin_settings_screen'], 30);
        add_action('acf/include_fields', [$this, 'add_general_portal_settings'], 10, 0);
        add_action('acf/include_fields', [$this, 'add_messages_settings'], 10, 0);
        add_action('acf/include_fields', [$this, 'add_access_settings'], 10, 0);
        add_filter('esc_html', [$this, 'allow_view_portal_button'], 10, 2);
        add_action('acf/save_post', [$this, 'save_access_settings'], 10, 1);
    }

    public function create_admin_settings_screen(): void {
        acf_add_options_page(array(
            'page_title'  => __('Document Portal Settings', 'simple-document-portal'),
            'menu_title'  => __('Portal Settings', 'simple-document-portal'),
            'parent_slug' => 'edit.php?post_type=document',
            'menu_slug'   => 'settings',
            'capability'  => 'manage_documents_options',
            'redirect'    => false,
        ));
    }

    /**
     * Add the "View portal" button to the text to be rendered in the settings page title.
     * Not ideal because it puts it in the <h1>, but beats any other hack that would require some serious CSS (or worse, JS) fuckery to display as expected visually.
     *
     * @param  $safe_text
     * @param  $text
     *
     * @return string
     */
    public function allow_view_portal_button($safe_text, $text): string {
        if ($safe_text === __('Document Portal Settings', 'simple-document-portal')) {
            $safe_text .= $this->add_view_portal_button();
        }

        return $safe_text;
    }

    private function add_view_portal_button(): string {
        return '<a class="button page-title-action" href="' . get_post_type_archive_link('document') . '">View portal</a>';
    }

    /**
     * Add the general settings fields to the settings page.
     *
     * @return void
     */
    public function add_general_portal_settings(): void {
        acf_add_local_field_group(array(
            'key'                   => 'group_simple-document-portal__general-settings',
            'title'                 => 'General',
            'fields'                => array(
                array(
                    'key'           => 'field_simple-document-portal__page_title',
                    'label'         => 'Page title',
                    'name'          => 'page_title',
                    'type'          => 'text',
                    'default_value' => 'Document Portal',
                    'maxlength'     => 255
                ),
                array(
                    'key'               => 'field_document_folder_location',
                    'label'             => 'Server path of document folder',
                    'name'              => 'document_folder_location',
                    'type'              => 'text',
                    'disabled'          => true,
                    'default_value'     => PluginEntrypoint::get_protected_directory(),
                    'maxlength'         => 255,
                    'allow_in_bindings' => false
                ),
                array(
                    'key'        => 'field_6886fadbeabc8',
                    'label'      => 'Portal layout',
                    'name'       => 'portal_layout',
                    'type'       => 'group',
                    'layout'     => 'row',
                    'sub_fields' => array(
                        array(
                            'key'           => 'field_layout_switch_breakpoint',
                            'label'         => 'Layout switch breakpoint',
                            'name'          => 'layout_switch_breakpoint',
                            'type'          => 'number',
                            'instructions'  => 'The document folders will be displayed as an "accordion" unless there is at least this much available space, in which case a "tabs" layout will be used. Use 0 to always show tabs, or a very large number (larger than the maximum available container width you expect) to always show an accordion. Note: This may have no effect if your theme is overriding the template provided by the plugin.',
                            'default_value' => 768,
                            'min'           => 0,
                            'max'           => 5000,
                            'step'          => 1,
                            'append'        => 'pixels',
                        ),
                    ),
                ),
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'settings',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => true,
            'description'           => '',
            'show_in_rest'          => false,
        ));
    }

    /**
     * Add the messages settings fields to the settings page.
     *
     * @return void
     */
    public function add_messages_settings(): void {
        acf_add_local_field_group(array(
            'key'                   => 'group_simple-document-portal__messages-settings',
            'title'                 => 'Messages',
            'fields'                => array(
                array(
                    'key'          => 'field_688af60df7c2a',
                    'label'        => 'Permission error',
                    'name'         => 'permission_error_message',
                    'type'         => 'group',
                    'instructions' => 'Message shown on the portal page to users who are logged in, but don\'t have permission to read/download documents.',
                    'layout'       => 'block',
                    'sub_fields'   => array(
                        array(
                            'key'           => 'field_688af624f7c2b',
                            'label'         => 'Heading',
                            'name'          => 'heading',
                            'type'          => 'text',
                            'default_value' => 'No access',
                            'maxlength'     => 255
                        ),
                        array(
                            'key'           => 'field_688af62df7c2c',
                            'label'         => 'Description',
                            'name'          => 'description',
                            'type'          => 'textarea',
                            'default_value' => 'Sorry, you don\'t have access to the document portal.',
                            'rows'          => 4
                        ),
                    ),
                ),
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'settings',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => true,
            'description'           => '',
            'show_in_rest'          => false,
        ));
    }

    /**
     * Add the access settings fields to the settings page.
     *
     * @return void
     */
    public function add_access_settings(): void {
        $fields = array_map(function($capability) {
            $label = str_replace('_', ' ', ucfirst($capability));
            if ($capability === 'read_documents') {
                $label = __('Read/download documents', 'simple-document-portal');
            }
            else if ($capability === 'manage_documents_options') {
                $label = __('Manage portal settings', 'simple-document-portal');
            }

            return $this->create_access_setting_field($capability, $label);
        }, array_keys(UserPermissions::get_capability_map()));

        acf_add_local_field_group(array(
            'key'                   => 'group_simple-document-portal__document-access-settings',
            'title'                 => 'Document Access',
            'fields'                => array_merge([
                // Add a message field to explain the purpose of this section
                array(
                    'key'     => 'field_simple-document-portal__document_access_instructions',
                    'type'    => 'message',
                    'message' => '<p>Permissions are granted to roles upon plugin activation according to a mapping with built-in roles. For example, <code>manage_portal_settings</code> is granted to roles that have the built-in <code>manage_options</code> capability. You can grant or revoke permissions beyond that from here, or make more complex modifications in your own custom user role code (the result of which should be reflected here automatically).</p><p>For simplicity, this form only shows roles that currently have at least one active user on the site.</p><p>To prevent accidental changes that cannot be quickly reversed, you cannot modify your own roles\' capabilities from here.</p>',
                ),
            ], $fields),
            'location'              => array(
                array(
                    array(
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'settings',
                    ),
                ),
            ),
            'menu_order'            => 2,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => true,
            'description'           => '',
            'show_in_rest'          => false,
        ));
    }

    private function create_access_setting_field(string $capability, string $label): array {
        $field_key = 'field_document_permission_' . $capability;
        $all_roles = wp_roles()->roles;
        $roles_with_permission = UserPermissions::get_roles_with_permission($capability);
        $filtered_roles = UserPermissions::get_roles_currently_in_use();

        $checkboxes = array_reduce($filtered_roles, function($carry, $role_key) use ($all_roles, $capability) {
            $carry[$role_key] = $all_roles[$role_key]['name'];

            return $carry;
        }, []);
        $preselected = array_intersect(array_keys($roles_with_permission), $filtered_roles);
        $non_editable = array_intersect($this->get_non_editable_roles_for_capability($capability), $filtered_roles);
        $current_users_roles = wp_get_current_user()->roles;
        $non_editable = array_unique(array_merge($non_editable, $current_users_roles)); // to ensure users cannot remove their own role's capabilities

        return array(
            'key'               => $field_key,
            'label'             => $label,
            'name'              => $capability,
            'type'              => 'checkbox',
            'choices'           => $checkboxes,
            'default_value'     => $preselected,
            'disabled'          => $non_editable,
            'return_format'     => 'value',
            'allow_custom'      => false,
            'allow_in_bindings' => false,
            'layout'            => 'vertical',
            'toggle'            => false,
            'save_custom'       => false,
        );
    }

    /**
     * Get roles that have the given capability, but it should not be editable in the settings screen
     *
     * @param  string  $capability
     *
     * @return array
     */
    private function get_non_editable_roles_for_capability(string $capability): array {
        $roles_with_permission = UserPermissions::get_roles_with_permission($capability);
        $roles_with_mapped_permission = UserPermissions::get_roles_with_matching_default_permission($capability);

        return array_intersect(array_keys($roles_with_permission), array_keys($roles_with_mapped_permission));
    }

    /**
     * Handle capability changes on settings save
     *
     * @param  $post_id
     *
     * @return void
     */
    public function save_access_settings($post_id): void {
        // Ensure we are only responding to the folder management POST request
        // Note: We can't do a negative check and return to bail early here, because that will bail early for other options pages
        if (is_admin() && ($post_id === 'options') && $_POST['acf']) {
            // Confirm if any of the field keys start with 'field_document_permission_'
            $isCapabilitySettings = array_filter(array_keys($_POST['acf']), fn($key) => str_starts_with($key, 'field_document_permission_'));
            if ($isCapabilitySettings) {
                $permission_fields = array_filter($_POST['acf'], function($key) {
                    return str_starts_with($key, 'field_document_permission_');
                }, ARRAY_FILTER_USE_KEY);
                $wp_roles_instance = wp_roles();
                $filtered_roles = array_diff(
                    UserPermissions::get_roles_currently_in_use(),
                    wp_get_current_user()->roles, // do not allow users to change their own roles
                );

                // $roles_to_add = boxes checked in the form that are not disabled
                // $roles_to_remove = roles that are present in the form, are not checked in the form, and are editable
                foreach ($permission_fields as $capability_field => $roles_to_add) {
                    $capability = str_replace('field_document_permission_', '', $capability_field);
                    $currently_editable_roles = array_diff($filtered_roles, $this->get_non_editable_roles_for_capability($capability));

                    if (is_array($roles_to_add) && !empty($roles_to_add)) {
                        $filtered_roles_to_add = array_intersect($currently_editable_roles, $roles_to_add);
                        // If roles were selected and are editable/not the current user's role, add the capability to those roles
                        foreach ($filtered_roles_to_add as $role_key) {
                            $wp_roles_instance->add_cap($role_key, $capability);
                        }

                        // Remove the capability from roles that are currently editable, but were not selected in the form
                        $roles_to_remove = array_diff($currently_editable_roles, $roles_to_add);
                        if (!empty($roles_to_remove)) {
                            foreach ($roles_to_remove as $role_key) {
                                $wp_roles_instance->remove_cap($role_key, $capability);
                            }
                        }
                    }

                    // If no roles were selected, clear them from all editable roles that are visible in the form
                    else if (empty($roles_to_add)) {
                        foreach ($currently_editable_roles as $role_key) {
                            $wp_roles_instance->remove_cap($role_key, $capability);
                        }
                    }
                }

                // Clear the default options fields so we don't keep redundant data in wp_options
                $caps = array_keys(UserPermissions::get_capability_map());
                foreach ($caps as $cap) {
                    delete_option('options_' . $cap);
                    delete_option('_options_' . $cap);
                }
            }
        }
    }
}
