<?php

namespace Doubleedesign\SimpleDocumentPortal;

/**
 * Class for managing the custom post type for document folders in the admin area.
 * This includes adding ACF fields and handling folder-specific actions.
 * Should be loaded only in the back-end.
 */
class FoldersAdmin {

    public function __construct() {
        // Custom taxonomy management
        add_action('acf/init', [$this, 'create_folder_management_screen'], 15, 0);
        add_action('acf/load_value', [$this, 'preload_folders_in_management_form'], 10, 3);
        add_action('acf/save_post', [$this, 'save_folders'], 20, 1);

        // Taxonomy term fields
        add_action('acf/init', [$this, 'add_folder_prefix_field'], 5, 0); // this must run before the folder management screen is created

        // Display of folder terms in the admin when selecting them for a document post
        add_filter('wp_terms_checklist_args', [$this, 'persist_folder_order_in_wp_checklist'], 10, 2);
        add_filter('acf/fields/taxonomy/wp_list_categories', [$this, 'sort_folders_by_prefix_in_acf_category_style_lists'], 20, 2);
    }

    public function create_folder_management_screen(): void {
        acf_add_options_sub_page(
            array(
                'page_title'  => __('Folders', 'simple-document-portal'),
                'menu_title'  => __('Manage Folders', 'simple-document-portal'),
                'menu_slug'   => 'folders',
                'capability'  => 'manage_documents_options',
                'parent_slug' => 'edit.php?post_type=document',
                'redirect'    => false,
            )
        );

        // Get ACF fields for the folder taxonomy itself
        $folder_field_group = acf_get_local_field_group('group_folder-fields');
        $folder_fields = acf_get_local_fields($folder_field_group['key']);

        // Calculate the space remaining for the name field from the wrapper widths on all the $folder_fields
        $name_width = array_reduce($folder_fields, function($carry, $field) {
            if (isset($field['wrapper']['width']) && is_numeric($field['wrapper']['width'])) {
                return $carry + (int)$field['wrapper']['width'];
            }

            return $carry;
        }, 0);

        // Add a repeater field to the options page for managing folders, using the actual folder taxonomy fields as sub-fields
        acf_add_local_field_group(array(
            'key'    => 'group_folder-management-fields',
            'title'  => 'Folders',
            'fields' => array(
                array(
                    'key'               => 'field_folders',
                    'label'             => '',
                    'name'              => '',
                    'aria-label'        => '',
                    'type'              => 'repeater',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'layout'        => 'table',
                    'pagination'    => 0,
                    'min'           => 0,
                    'max'           => 0,
                    'collapsed'     => '',
                    'button_label'  => 'Add folder',
                    'rows_per_page' => 50,
                    'sub_fields'    => array_merge(
                        $folder_fields,
                        array(array(
                            'key'               => 'field_folder_name',
                            'label'             => 'Name',
                            'name'              => 'name',
                            'aria-label'        => '',
                            'type'              => 'text',
                            'instructions'      => '',
                            'required'          => 0,
                            'conditional_logic' => 0,
                            'wrapper'           => array(
                                'width' => $name_width,
                                'class' => '',
                                'id'    => '',
                            ),
                            'default_value'     => '',
                            'maxlength'         => '',
                            'allow_in_bindings' => 0,
                            'placeholder'       => '',
                            'prepend'           => '',
                            'append'            => '',
                            'parent_repeater'   => 'field_folders',
                        )),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'folders',
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
     * Load existing folder taxonomy terms into the ACF options form used to manage them.
     *
     * @param  $value
     * @param  $post_id
     * @param  $field
     *
     * @return void
     */
    public function preload_folders_in_management_form($value, $post_id, $field) {}

    /**
     * Intercept the save action for the Folders options page, and save/update taxonomy terms instead of an options value.
     *
     * @param  $post_id
     *
     * @return void
     */
    public function save_folders($post_id): void {
        // Ensure we are only responding to the folder management POST request
        if (!is_admin() || $post_id != 'options' || !isset($_POST['acf']['field_folders'])) {
            return;
        }

        // Clear the default options field so we don't store redundant data in the wp_options table
        update_field('field_folders', [], 'options');
    }

    /**
     * Add a prefix field to the folder taxonomy.
     *
     * @return void
     */
    public function add_folder_prefix_field(): void {
        acf_add_local_field_group(array(
            'key'    => 'group_folder-fields',
            'title'  => 'Folder details',
            'fields' => array(
                array(
                    'key'               => 'field_folder-prefix',
                    'label'             => 'Prefix',
                    'name'              => 'prefix',
                    'aria-label'        => '',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '20',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'maxlength'         => '',
                    'allow_in_bindings' => 0,
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => 'folder',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'high',
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
     * Persist the folder order in WP default admin checklists (do not move selected folders to the top)
     *
     * @param  array  $args
     * @param  int  $post_id
     *
     * @return array
     */
    public function persist_folder_order_in_wp_checklist(array $args, $post_id): array {
        if ($args['taxonomy'] !== 'folder') {
            return $args;
        }

        $args['checked_ontop'] = false;

        return $args;
    }

    /**
     * Sort folders by prefix when using ACF's category-style lists (they use wp_list_categories under the hood)
     *
     * @param  array  $args
     * @param  array  $field
     *
     * @return array
     */
    public function sort_folders_by_prefix_in_acf_category_style_lists(array $args, array $field): array {
        if (!isset($args['taxonomy']) || $args['taxonomy'] !== 'folder') {
            return $args;
        }

        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'prefix';

        return $args;
    }
}
