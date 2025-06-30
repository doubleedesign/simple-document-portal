<?php

namespace Doubleedesign\SimpleDocumentPortal;

class AdminSettings {

	public function __construct() {
		add_action('acf/init', [$this, 'create_admin_settings_screen'], 6);
		add_action('acf/include_fields', [$this, 'add_general_portal_settings'], 10, 0);
		add_action('acf/include_fields', [$this, 'add_access_settings'], 10, 0);
		add_filter('esc_html', [$this, 'allow_view_portal_button'], 10, 2);

		// TODO: Handle capability changes on settings save
	}

	function create_admin_settings_screen(): void {
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
	 * @param $safe_text
	 * @param $text
	 * @return string
	 */
	function allow_view_portal_button($safe_text, $text): string {
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
	 * @return void
	 */
	function add_general_portal_settings(): void {
		acf_add_local_field_group(array(
			'key'                   => 'group_simple-document-portal__general-settings',
			'title'                 => 'General',
			'fields'                => array(
				array(
					'key'               => 'field_simple-document-portal__page_title',
					'label'             => 'Page title',
					'name'              => 'page_title',
					'aria-label'        => '',
					'type'              => 'text',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => 'Document Portal',
					'maxlength'         => '',
					'allow_in_bindings' => 0,
					'placeholder'       => '',
					'prepend'           => '',
					'append'            => '',
				),
				array(
					'key'               => 'field_document_folder_location',
					'label'             => 'Server path of document folder',
					'name'              => 'document_folder_location',
					'aria-label'        => '',
					'type'              => 'text',
					'instructions'      => 'IMPORTANT: Changing this will not move existing files. You must move them manually to the new location.',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => PluginEntrypoint::get_protected_directory(),
					'maxlength'         => '',
					'allow_in_bindings' => 0,
					'placeholder'       => '',
					'prepend'           => '',
					'append'            => '',
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
			'show_in_rest'          => 0,
		));
	}

	/**
	 * Add the access settings fields to the settings page.
	 * @return void
	 */
	function add_access_settings(): void {
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
					'key'               => 'field_simple-document-portal__document_access_instructions',
					'type'              => 'message',
					'message'           => '<p>Permissions are granted to roles upon plugin activation according to a mapping with built-in roles. For example, <code>manage_portal_settings</code> is granted to roles that have the built-in <code>manage_options</code> capability. You can grant or revoke permissions beyond that from here, or make more complex modifications in your own custom user role code (the result of which should be reflected here automatically).</p><p>For simplicity, this form only shows roles that currently have at least one active user on the site.</p>',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
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
			'show_in_rest'          => 0,
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
			'aria-label'        => '',
			'type'              => 'checkbox',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => array(
				'width' => '',
				'class' => '',
				'id'    => '',
			),
			'choices'           => $checkboxes,
			'default_value'     => $preselected,
			'disabled'          => $non_editable,
			'return_format'     => 'value',
			'allow_custom'      => 0,
			'allow_in_bindings' => 0,
			'layout'            => 'vertical',
			'toggle'            => 0,
			'save_custom'       => 0,
		);
	}
}
