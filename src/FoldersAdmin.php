<?php

namespace Doubleedesign\SimpleDocumentPortal;

/**
 * Class for managing the custom post type for document folders in the admin area.
 * This includes adding ACF fields and handling folder-specific actions,
 * including the creation of the custom folder taxonomy management screen.
 * Should be loaded only in the back-end.
 */
class FoldersAdmin {

    public function __construct() {
        // Custom taxonomy management
        add_action('acf/init', [$this, 'create_folder_management_screen'], 15, 0);
        add_action('acf/include_fields', [$this, 'add_folder_management_fields'], 12, 0);
        add_filter('gettext', [$this, 'update_repeater_labels'], 10, 3);
        add_action('acf/load_value', [$this, 'preload_folders_in_management_form'], 10, 3);
        add_action('acf/save_post', [$this, 'save_folders'], 5, 1);

        // Taxonomy term fields
        add_action('acf/include_fields', [$this, 'add_folder_term_fields'], 5, 0); // this must run before the folder management screen is created and its fields are added

        // Display of folder terms in the admin when selecting them for a document post
        add_filter('wp_terms_checklist_args', [$this, 'persist_folder_order_in_wp_checklist'], 10, 2);
        add_filter('acf/fields/taxonomy/wp_list_categories', [$this, 'sort_folders_by_prefix_in_acf_category_style_lists'], 20, 2);
    }

    public function create_folder_management_screen(): void {
        acf_add_options_page(
            array(
                'page_title'         => __('Folders', 'simple-document-portal'),
                'menu_title'         => __('Manage Folders', 'simple-document-portal'),
                'parent_slug'        => 'edit.php?post_type=document',
                'menu_slug'          => 'folders',
                'capability'         => 'manage_documents_options',
                'redirect'           => false,
                'update_button'      => __('Update folders', 'simple-document-portal'),
                'updated_message'    => __('Folders updated', 'simple-document-portal'),
                'position'           => 20
            )
        );
    }

    public function add_folder_management_fields(): void {
        // Get ACF fields for the folder taxonomy itself
        $folder_field_group = acf_get_local_field_group('group_folder-fields');
        $folder_fields = $folder_field_group ? acf_get_local_fields($folder_field_group['key']) : [];

        $common_field_settings = array(
            'id' => array(
                'key'               => 'field_folder-id',
                'type'              => 'text',
                'label'             => 'ID',
                'name'              => 'id',
                // can't use disabled because that will stop the data going through on save
                'readonly'          => true,
                'allow_in_bindings' => false
            ),
            'name' => array(
                'key' 			           => 'field_folder-name',
                'label'             => 'Name',
                'name'              => 'name',
                'type'              => 'text',
                'required'          => true,
                'maxlength'         => 100,
                'allow_in_bindings' => false,
            ),
            'slug' => array(
                'key'               => 'field_folder-slug',
                'label'             => 'URL slug',
                'name'              => 'slug',
                'type'              => 'text',
                'maxlength'         => 100,
                'allow_in_bindings' => false,
            )
        );

        // Add a repeater field to the options page for managing folders, using the actual folder taxonomy fields as sub-fields
        acf_add_local_field_group(array(
            'key'                   => 'group_folder-management-fields',
            'title'                 => 'Folders',
            'fields'                => array(
                array(
                    'key'     => 'field_folder-management-notes',
                    'type'    => 'message',
                    'message' => '<ul><li>Folders and subfolders will automatically be sorted by prefix after saving.</li><li>To add subfolders to a new folder, click "update folders" to save the top-level folder first.</li></ul>',
                    'wrapper' => array(
                        'class' => 'notice notice-info',
                    ),
                ),
                array(
                    'key'     => 'field_folder-management-warning',
                    'type'    => 'message',
                    'message' => '<ul><li>Deleting a folder deletes all its subfolders.</li><li>Changes (including deletions) are not saved until you click "Update folders", so if you accidentally delete something, refresh the page without saving to restore to the last saved state.</li><li>After saving, deleted folders cannot be recovered from within the admin. Restoring a previous version of the folders from a database backup may possible as a last resort, but the availability of a backup with the exact desired configuration is never guaranteed.</li></ul>',
                    'wrapper' => array(
                        'class' => 'notice notice-warning'
                    ),
                ),
                array(
                    'key'           => 'field_folders',
                    'name'          => 'field_folders_repeater',
                    'type'          => 'repeater',
                    'required'      => 0,
                    'layout'        => 'row',
                    'pagination'    => false,
                    'button_label'  => 'Add folder',
                    'rows_per_page' => 100,
                    'sub_fields'    => array(
                        ...$folder_fields ?? [],
                        array(
                            'key'               => 'field_folder-name',
                            'parent_repeater'   => 'field_folders',
                            ...$common_field_settings['name']
                        ),
                        array(
                            'key'               => 'field_folder-slug',
                            'parent_repeater'   => 'field_folders',
                            'instructions'      => 'Will be automatically generated if left empty. Must not include spaces or special characters.',
                            ...$common_field_settings['slug']
                        ),
                        array(
                            'key'               => 'field_folder-id',
                            'parent_repeater'   => 'field_folders',
                            'instructions'      => 'Auto-generated and must be unique.',
                            ...$common_field_settings['id']
                        ),
                        array(
                            'key'               => 'field_subfolders',
                            'name'              => 'field_subfolders_repeater',
                            'label'             => 'Subfolders',
                            'type'              => 'repeater',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field'    => 'field_folder-id',
                                        'operator' => '!=empty',
                                    ),
                                ),
                            ),
                            'required'          => 0,
                            'layout'            => 'table',
                            'pagination'        => false,
                            'button_label'      => 'Add subfolder',
                            'rows_per_page'     => 100,
                            'sub_fields'        => array(
                                ...$folder_fields ?? [],
                                array(
                                    'parent_repeater'   => 'field_subfolders',
                                    ...$common_field_settings['name']
                                ),
                                array(
                                    'parent_repeater'   => 'field_subfolders',
                                    ...$common_field_settings['slug']
                                ),
                                array(
                                    'parent_repeater'   => 'field_subfolders',
                                    ...$common_field_settings['id']
                                ),
                            )
                        )
                    )
                ),
            ),
            'location'              => array(
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
            'show_in_rest'          => false,
        ));
    }

    public function update_repeater_labels($translated_text, $text, $domain): ?string {
        // Rather than check where we are at the top (which will do it for every string),
        // check after we have ascertained that we're looking at the right piece of text.
        global $current_screen;
        if ($translated_text === 'Remove row' && $domain === 'acf' && $current_screen->id === 'document_page_folders') {
            return __('Delete folder', 'simple-document-portal');
        }

        // \Symfony\Component\VarDumper\VarDumper::dump($current_screen);

        return $translated_text;
    }

    /**
     * Load existing folder taxonomy terms into the ACF options form used to manage them.
     *
     * @param  $value
     * @param  $post_id
     * @param  $field
     *
     * @return mixed
     */
    public function preload_folders_in_management_form($value, $post_id, $field): mixed {
        if ($field['name'] === 'field_folders_repeater' && $field['parent'] === 'group_folder-management-fields' && $post_id === 'options') {
            $top_level_folders = get_terms(array(
                'taxonomy'   => 'folder',
                'hide_empty' => false,
                'parent'     => 0
            ));

            return array_map(function($folder) {
                $subfolders = get_terms(array(
                    'taxonomy'   => 'folder',
                    'hide_empty' => false,
                    'parent'     => $folder->term_id,
                ));

                return array(
                    'field_folder-id'      => $folder->term_id,
                    'field_folder-name'    => $folder->name,
                    'field_folder-prefix'  => get_term_meta($folder->term_id, 'prefix', true) ?? '',
                    'field_folder-slug'    => $folder->slug,
                    'field_subfolders'     => array_map(function($subfolder) {
                        return array(
                            'field_folder-id'     => $subfolder->term_id,
                            'field_folder-name'   => $subfolder->name,
                            'field_folder-prefix' => get_term_meta($subfolder->term_id, 'prefix', true) ?? '',
                            'field_folder-slug'   => $subfolder->slug,
                        );
                    }, $subfolders)
                );
            }, $top_level_folders);
        }

        return $value;
    }

    /**
     * Intercept the save action for the Folders options page, and save/update taxonomy terms instead of an options value.
     *
     * @param  $post_id
     *
     * @return void
     */
    public function save_folders($post_id): void {
        // Ensure we are only responding to the folder management POST request
        // Note: We can't do a negative check and return to bail early here, because that will bail early for other options pages
        if (is_admin() && ($post_id === 'options' && isset($_POST['acf']['field_folders']))) {
            $submitted_data = $_POST['acf']['field_folders'];
            if ($submitted_data === '') {
                $submitted_top_level_ids = [];
                $submitted_subfolder_ids = [];
            }
            else {
                $submitted_top_level_ids = array_column($submitted_data, 'field_folder-id');
                $submitted_subfolder_ids = array_reduce($submitted_data, function($carry, $item) {
                    if (is_array($item['field_subfolders'])) {
                        return array_merge($carry, array_column($item['field_subfolders'], 'field_folder-id'));
                    }

                    return $carry;
                }, []);
            }
            $existing_folders = get_terms(array(
                'taxonomy'   => 'folder',
                'hide_empty' => false,
            ));
            $submitted_ids = array_merge($submitted_top_level_ids, $submitted_subfolder_ids);
            $existing_ids = array_column($existing_folders, 'term_id');

            $new_ids = [];
            if (is_array($submitted_data)) {
                array_walk($submitted_data, function(&$item) use ($new_ids) {
                    $new_id = $this->save_folder($item);
                    array_push($new_ids, $new_id);
                    if (is_array($item['field_subfolders'])) {
                        array_walk($item['field_subfolders'], function($subfolder) use ($new_ids, $item) {
                            $new_id = $this->save_folder(array(
                                'parent' => $item['field_folder-id'],
                                ...$subfolder
                            ));
                            array_push($new_ids, $new_id);
                        });
                    }
                });
            }

            // Clear the default field that this form creates so we don't store redundant submitted_data in the wp_options table
            update_field('field_folders', [], 'options');

            // Delete any folders that were not included in the submitted data or were not just created
            $to_delete = array_diff($existing_ids, array_merge($submitted_ids, $new_ids));
            array_walk($to_delete, function($id) {
                wp_delete_term($id, 'folder');
                delete_term_meta($id, 'prefix');
            });
        }
    }

    protected function save_folder($item): int {
        $id = $item['field_folder-id'];
        // if the ID is empty, this is a new term to be created
        if (empty($id)) {
            $term = wp_insert_term(
                $item['field_folder-name'],
                'folder',
                array(
                    'slug'   => $item['field_folder-slug'] ?? '',
                    'parent' => isset($item['parent']) ? (int)$item['parent'] : 0,
                )
            );
            if (!is_wp_error($term)) {
                $id = (int)$term['term_id'];
                update_term_meta($id, 'prefix', $item['field_folder-prefix'] ?? '');
            }
            else {
                error_log(print_r($term, true));
                acf_add_admin_notice('Error creating folder: ' . $term->get_error_message(), 'error');
            }
        }
        // Otherwise, find term by existing ID and update it
        else {
            wp_update_term(
                $id,
                'folder',
                array(
                    'name' => $item['field_folder-name'],
                    'slug' => $item['field_folder-slug'],
                    // TODO: Allow for parent to be changed
                )
            );
            update_term_meta($id, 'prefix', $item['field_folder-prefix'] ?? '');
        }

        return (int)$id;
    }

    /**
     * Add a prefix field to the folder taxonomy.
     *
     * @return void
     */
    public function add_folder_term_fields(): void {
        acf_add_local_field_group(array(
            'key'                   => 'group_folder-fields',
            'title'                 => 'Folder details',
            'fields'                => array(
                array(
                    'key'               => 'field_folder-prefix',
                    'label'             => 'Prefix',
                    'name'              => 'prefix',
                    'aria-label'        => '',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'default_value'     => '',
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
            'show_in_rest'          => false,
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
