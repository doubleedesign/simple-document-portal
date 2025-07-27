<?php

namespace Doubleedesign\SimpleDocumentPortal;
use WP_Screen, WP_Term;

/**
 * Class for managing the custom taxonomy for document folders.
 * To be loaded both in the admin and front-end.
 */
class Folders {
    public function __construct() {
        add_action('init', [$this, 'register_taxonomy'], 20);

        add_filter('get_term', [$this, 'add_prefix_to_single_folder_title'], 10, 2);
        add_filter('get_terms_defaults', [$this, 'sort_folders_by_prefix_in_wp_term_query'], 20, 2);
    }

    /**
     * Register the custom taxonomy for document folders.
     *
     * @return void
     */
    public function register_taxonomy(): void {
        $labels = array(
            'name'                     => _x('Document Folders', 'taxonomy general name', 'simple-document-portal'),
            'singular_name'            => _x('Folder', 'taxonomy singular name', 'simple-document-portal'),
            'search_items'             => __('Search folders', 'simple-document-portal'),
            'all_items'                => __('All folders', 'simple-document-portal'),
            'edit_item'                => __('Edit folder', 'simple-document-portal'),
            'update_item'              => __('Update folder', 'simple-document-portal'),
            'add_new_item'             => __('Add new folder', 'simple-document-portal'),
            'new_item_name'            => __('New folder name', 'simple-document-portal'),
            'menu_name'                => __('Folders', 'simple-document-portal'),
            'not_found'                => __('No folders found.', 'simple-document-portal'),
            'parent_item'              => __('Parent folder', 'simple-document-portal'),
            'name_field_description'   => '',
            'parent_field_description' => '',
        );

        register_taxonomy('folder', ['document'], [
            'hierarchical'      => true,
            'label'             => __('Folders', 'simple-document-portal'),
            'labels'            => $labels,
            'show_ui'           => false, // disable default WordPress UI, we are using custom ACF fields/forms
            'show_in_menu'      => true, // admin sidebar menu
            'show_in_nav_menus' => false, // nav menus for front-end
            'show_tagcloud'     => false,
            'rewrite'           => ['slug' => 'documents', 'with_front' => false],
            'show_in_rest'      => true,
        ]);
    }

    /**
     * Add the prefix to the folder title when displaying it (in most locations).
     *
     * @param  WP_Term  $term
     * @param  string  $taxonomy
     *
     * @return WP_Term
     */
    public function add_prefix_to_single_folder_title(WP_Term $term, string $taxonomy): WP_Term {
        // This one needs to be run on get_term not get_folder because of places that use get_term that we want this done
        if ($taxonomy !== 'folder') {
            return $term;
        }

        // Do not modify if on the admin screen with the list table of all folders, as there is a separate column for the prefix
        if (is_admin()) {
            $screen_id = WP_Screen::get()->id ?? '';
            if ($screen_id === 'edit-folder') {
                return $term;
            }
        }

        // Everywhere else including the front-end, we want to add the prefix to the folder name
        $prefix = get_term_meta($term->term_id, 'prefix', true);
        if ($prefix) {
            $term->name = $prefix . ' ' . $term->name;
        }

        return $term;
    }

    /**
     * Sort folders by prefix when retrieving terms, instead of by name.
     *
     * @param  array  $defaults
     * @param  ?array  $taxonomies
     *
     * @return array
     */
    public function sort_folders_by_prefix_in_wp_term_query(array $defaults, ?array $taxonomies): array {
        // If there are no folders, bail early
        if (empty($taxonomies)) {
            return $defaults;
        }

        // This one needs to be run on get_terms not get_folders because ACF uses apply_filters('get_terms', ...)
        if (!in_array('folder', $taxonomies, true)) {
            return $defaults;
        }

        // If we are in the term list table admin screen, skip sorting as that has its own sorting options
        if (is_admin()) {
            $screen_id = WP_Screen::get()->id ?? '';
            if ($screen_id === 'edit-folder') {
                return $defaults;
            }
        }

        if (isset($defaults['orderby']) && $defaults['orderby'] === 'name') {
            $defaults['orderby'] = 'meta_value';
            $defaults['meta_key'] = 'prefix';
        }

        return $defaults;
    }

}
