<?php
namespace Doubleedesign\SimpleDocumentPortal;

/**
 * Class for managing the custom post type for documents.
 * To be loaded both in the admin and front-end.
 */
class Documents {

    public function __construct() {
        add_action('init', [$this, 'register_cpt'], 1);
    }

    /**
     * Register the custom post type for documents.
     *
     * @return void
     */
    public function register_cpt(): void {
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

}
