<?php

namespace Doubleedesign\SimpleDocumentPortal;

class AdminSettings {

	public function __construct() {
		add_action('acf/init', [$this, 'create_admin_settings_screen'], 30);
		add_action('acf/include_fields', [$this, 'add_general_portal_settings'], 10, 0);
		add_action('acf/include_fields', [$this, 'add_messages_settings'], 10, 0);
		add_action('acf/include_fields', [$this, 'add_access_settings'], 10, 0);
		add_filter('esc_html', [$this, 'allow_view_portal_button'], 10, 2);

		// TODO: Handle capability changes on settings save
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
		if($safe_text === __('Document Portal Settings', 'simple-document-portal')) {
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
			'key'                   => 'group_688af60d193a7',
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
			if($capability === 'read_documents') {
				$label = __('Read/download documents', 'simple-document-portal');
			}
			else if($capability === 'manage_documents_options') {
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
					'message' => '<p>Permissions are granted to roles upon plugin activation according to a mapping with built-in roles. For example, <code>manage_portal_settings</code> is granted to roles that have the built-in <code>manage_options</code> capability. You can grant or revoke permissions beyond that from here, or make more complex modifications in your own custom user role code (the result of which should be reflected here automatically).</p><p>For simplicity, this form only shows roles that currently have at least one active user on the site.</p>',
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
		$field_key = 'field_' . md5($capability);
		$all_roles = wp_roles()->roles;
		$roles_with_mapped_permission = UserPermissions::get_roles_with_matching_default_permission($capability);
		$roles_with_permission = UserPermissions::get_roles_with_permission($capability);
		$matching_default_permission = UserPermissions::get_mapped_built_in_capability($capability);
		$filtered_roles = UserPermissions::get_roles_currently_in_use();

		$preselected = array_intersect(array_keys($roles_with_permission), $filtered_roles);
		$non_editable = array_intersect(array_keys($roles_with_permission), array_keys($roles_with_mapped_permission), $filtered_roles);
		$checkboxes = array_reduce($filtered_roles, function($carry, $role_key) use ($all_roles, $capability) {
			$carry[$role_key] = $all_roles[$role_key]['name'];

			return $carry;
		}, []);

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
}
