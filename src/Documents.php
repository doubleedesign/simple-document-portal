<?php
namespace Doubleedesign\SimpleDocumentPortal;
use WP_Post, WP_Screen, WP_Term;
use WP_Term_Query;

class Documents {

	public function __construct() {
		add_action('init', [$this, 'register_cpt'], 1);
		add_action('init', [$this, 'register_taxonomy'], 20);
		add_action('acf/include_fields', [$this, 'add_file_field'], 10, 0);
		add_action('acf/include_fields', [$this, 'add_folder_prefix_field'], 10, 0);
		add_filter('get_term', [$this, 'add_prefix_to_single_folder_title'], 10, 2);
		add_filter('get_terms_defaults', [$this, 'sort_folders_by_prefix'], 20, 2);
		add_filter('wp_terms_checklist_args', [$this, 'persist_folder_order_in_checklist'], 10, 2);

		add_action('save_post', [$this, 'populate_empty_title_on_save'], 20, 2);
		add_filter('wp_insert_post_data', [$this, 'always_set_documents_to_private'], 10, 2);
	}


	/**
	 * Register the custom post type for documents.
	 * @return void
	 */
	function register_cpt(): void {
		$labels = array(
			'name'                  => _x('Documents', 'Post Type General Name', 'simple-document-portal'),
			'singular_name'         => _x('Document', 'Post Type Singular Name', 'simple-document-portal'),
			'menu_name'             => __('Documents', 'simple-document-portal'),
			'name_admin_bar'        => __('Document', 'simple-document-portal'),
			'archives'              => __('Document Archives', 'simple-document-portal'),
			'attributes'            => __('Document Attributes', 'simple-document-portal'),
			'parent_item_colon'     => __('Parent Document:', 'simple-document-portal'),
			'all_items'             => __('All Documents', 'simple-document-portal'),
			'add_new_item'          => __('Add New Document', 'simple-document-portal'),
			'add_new'               => __('Add New', 'simple-document-portal'),
			'new_item'              => __('New Document', 'simple-document-portal'),
			'edit_item'             => __('Edit Document', 'simple-document-portal'),
			'update_item'           => __('Update Document', 'simple-document-portal'),
			'view_item'             => __('View Document', 'simple-document-portal'),
			'view_items'            => __('View Documents', 'simple-document-portal'),
			'search_items'          => __('Search Documents', 'simple-document-portal'),
			'not_found'             => __('Not found', 'simple-document-portal'),
			'not_found_in_trash'    => __('Not found in Trash', 'simple-document-portal'),
			'featured_image'        => __('Preview Image', 'simple-document-portal'),
			'set_featured_image'    => __('Set featured image', 'simple-document-portal'),
			'remove_featured_image' => __('Remove preview image', 'simple-document-portal'),
			'use_featured_image'    => __('Use as preview image', 'simple-document-portal'),
			'insert_into_item'      => __('Add to document', 'simple-document-portal'),
			'uploaded_to_this_item' => __('Uploaded to this document', 'simple-document-portal'),
			'items_list'            => __('Documents list', 'simple-document-portal'),
			'items_list_navigation' => __('Documents list navigation', 'simple-document-portal'),
			'filter_items_list'     => __('Filter documents list', 'simple-document-portal'),
		);
		$rewrite = array(
			'slug'       => 'document',
			'with_front' => false,
			'pages'      => false, // we don't expect to need WP-provided pagination
			'feeds'      => false,
		);
		$capabilities = array(
			'edit_post'             => 'edit_private_posts',
			'read_post'             => 'read_private_posts',
			'delete_post'           => 'delete_private_posts',
			'edit_posts'            => 'edit_private_posts',
			'edit_others_posts'     => 'edit_others_posts',
			'publish_posts'         => 'publish_posts',
		);
		$args = array(
			'label'               => __('Document', 'simple-document-portal'),
			'description'         => __('Documents for users with portal access.', 'simple-document-portal'),
			'labels'              => $labels,
			'supports'            => array('title'),
			'taxonomies'          => array('folder'),
			'hierarchical'        => false,
			'show_ui'             => true,
			'show_in_admin_bar'   => true, // admin top bar menu
			'show_in_menu'        => true, // admin sidebar menu
			'show_in_nav_menus'   => false, // nav menus for front-end
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-media-document',
			'can_export'          => false,
			'rewrite'             => $rewrite,
			'has_archive'         => 'portal',
			'show_in_rest'        => false, // disables Gutenberg, and we currently don't need it for anything else
			'exclude_from_search' => false,
			'publicly_queryable'  => true, // required for the archive page to work
			'capabilities'        => $capabilities,
		);

		register_post_type('document', $args);
	}

	/**
	 * Register the custom taxonomy for document folders.
	 * @return void
	 */
	function register_taxonomy(): void {
		$labels = array(
			'name'              => _x('Document Folders', 'taxonomy general name', 'simple-document-portal'),
			'singular_name'     => _x('Folder', 'taxonomy singular name', 'simple-document-portal'),
			'search_items'      => __('Search folders', 'simple-document-portal'),
			'all_items'         => __('All folders', 'simple-document-portal'),
			'edit_item'         => __('Edit folder', 'simple-document-portal'),
			'update_item'       => __('Update folder', 'simple-document-portal'),
			'add_new_item'      => __('Add new folder', 'simple-document-portal'),
			'new_item_name'     => __('New folder name', 'simple-document-portal'),
			'menu_name'         => __('Folders', 'simple-document-portal'),
			'not_found'         => __('No folders found.', 'simple-document-portal'),
			'parent_item'       => __('Parent folder', 'simple-document-portal'),
			'name_field_description' => '',
			'parent_field_description' => '',
		);

		register_taxonomy('folder', ['document'], [
			'hierarchical'      => true,
			'label'             => __('Folders', 'simple-document-portal'),
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => true, // admin sidebar menu
			'show_in_nav_menus' => false, // nav menus for front-end
			'show_tagcloud'     => false,
			'rewrite'           => ['slug' => 'documents', 'with_front' => false],
			'show_in_rest'      => true,
		]);
	}

	/**
	 * Add a file field to the document post type.
	 * @return void
	 */
	function add_file_field(): void {
		acf_add_local_field_group(array(
			'key'                   => 'group_document-fields',
			'title'                 => 'File',
			'fields'                => array(
				array(
					'key'               => 'field_protected_document_file',
					'label'             => 'File',
					'name'              => 'protected_document_file',
					'aria-label'        => '',
					'type'              => 'file',
					'instructions'      => '',
					'required'          => 1,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'return_format'     => 'array',
					'library'           => 'uploadedTo',
					'min_size'          => '',
					'max_size'          => '',
					'mime_types'        => '',
					'allow_in_bindings' => 0,
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'document',
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
	 * Add a prefix field to the folder taxonomy.
	 * @return void
	 */
	function add_folder_prefix_field(): void {
		acf_add_local_field_group(array(
			'key' => 'group_folder-fields',
			'title' => 'Folder details',
			'fields' => array(
				array(
					'key' => 'field_folder-prefix',
					'label' => 'Prefix',
					'name' => 'prefix',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'allow_in_bindings' => 0,
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'taxonomy',
						'operator' => '==',
						'value' => 'folder',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'high', // Note: Actually putting this at the top of the forms is handled by JS
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
			'show_in_rest' => 0,
		));
	}

	/**
	 * Add the prefix to the folder title when displaying it (in most locations).
	 *
	 * @param WP_Term $term
	 * @param string $taxonomy
	 * @return WP_Term
	 */
	function add_prefix_to_single_folder_title(WP_Term $term, string $taxonomy): WP_Term {
		// This one needs to be run on get_term not get_folder because of places that use get_term that we want this done
		if ($taxonomy !== 'folder') return $term;

		// Do not modify if on the admin screen with the list table of all folders, as there is a separate column for the prefix
		$screen_id = WP_Screen::get()->id ?? '';
		if (is_admin() && ($screen_id === 'edit-folder')) return $term;

		// Everywhere else including the front-end, we want to add the prefix to the folder name
		$prefix = get_term_meta($term->term_id, 'prefix', true);
		if ($prefix) {
			$term->name = $prefix . ' ' . $term->name;
		}

		return $term;
	}

	/**
	 * Add the prefix to the folder titles when displaying them in a list.
	 * @param array $defaults
	 * @param array $taxonomies
	 * @return array
	 */
	function sort_folders_by_prefix($defaults, array $taxonomies): array {
		// This one needs to be run on get_terms not get_folders because ACF uses apply_filters('get_terms', ...)
		if(!in_array('folder', $taxonomies, true)) return $defaults;

		// If we are in the term list table admin screen, skip sorting as that has its own sorting options
		$screen_id = WP_Screen::get()->id ?? '';
		if(is_admin() && $screen_id === 'edit-folder') return $defaults;

		if($defaults['orderby'] === 'name') {
			$defaults['orderby'] = 'meta_value';
			$defaults['meta_key'] = 'prefix';
		}

		return $defaults;
	}

	/**
	 * Persist the folder order in checklists (e.g., document post edit screen)
	 * - do not move selected folders to the top.
	 * @param array $args
	 * @param int $post_id
	 * @return array
	 */
	function persist_folder_order_in_checklist(array $args, $post_id): array {
		if ($args['taxonomy'] !== 'folder') return $args;

		$args['checked_ontop'] = false;

		return $args;
	}

	/**
	 * Populate the post title with the attachment title or attachment filename if it's empty or 'Auto Draft'.
	 * @param int $post_id
	 * @param WP_Post $post
	 * @return void
	 */
	function populate_empty_title_on_save(int $post_id, WP_Post $post): void {
		if($post->post_type !== 'document' || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		if(empty($post->post_title) || $post->post_title === 'Auto Draft') {
			$file_id = (int)get_post_meta($post_id, 'protected_document_file', true);
			if($file_id) {
				wp_update_post([
					'ID' => $post_id,
					'post_title' => get_the_title($file_id) ?? get_post_meta($file_id, '_wp_attached_file', true)
				]);
			}
		}
	}

	/**
	 * Always set documents to private status on save, unless they are drafts.
	 * There should also be some CSS to hide other options for clarity
	 * there is not filter to stop them rendering in the DOM at all)
	 * @param $data
	 * @param $postarr
	 * @return array
	 */
	function always_set_documents_to_private($data, $postarr): array {
		if ($data['post_type'] === 'document' && !in_array($data['post_status'], ['draft', 'trash', 'auto-draft'], true)) {
			$data['post_status'] = 'private';
			$data['post_password'] = '';
		}

		return $data;
	}

}
