<?php

namespace Doubleedesign\SimpleDocumentPortal;
use WP_Post;

/**
 * Class for managing the custom post type for documents in the admin area.
 * This includes adding ACF fields, handling save actions, and setting post status.
 * Should be loaded only in the back-end.
 * Note: UI-specific customisations that don't affect data are located in the AdminUI class.
 */
class DocumentsAdmin {

    public function __construct() {
        // Document CPT fields
        add_action('acf/include_fields', [$this, 'add_document_fields'], 10, 0);

        // Document CPT save actions
        add_action('save_post', [$this, 'populate_empty_title_on_save'], 20, 2);
        add_filter('wp_insert_post_data', [$this, 'always_set_documents_to_private'], 10, 2);
    }

    /**
     * Add a file and folder ACF fields to the document post type.
     *
     * @return void
     */
    public function add_document_fields(): void {
        acf_add_local_field_group(array(
            'key'                   => 'group_document-fields',
            'title'                 => 'File',
            'fields'                => array(
                array(
                    'key'           => 'field_protected_document_file',
                    'label'         => 'File',
                    'name'          => 'protected_document_file',
                    'type'          => 'file',
                    'required'      => true,
                    'return_format' => 'array',
                    'library'       => 'uploadedTo',
                ),
                array(
                    'key'               => 'field_document_folder',
                    'label'             => 'Folder',
                    'name'              => 'folder',
                    'type'              => 'taxonomy',
                    'taxonomy'          => 'folder',
                    'add_term'          => false,
                    'save_terms'        => true,
                    'load_terms'        => true,
                    'return_format'     => 'id',
                    'field_type'        => 'select',
                    'allow_null'        => true,
                    'allow_in_bindings' => false,
                    'bidirectional'     => false,
                    'multiple'          => false,
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
            'show_in_rest'          => false,
        ));
    }

    /**
     * Populate the post title with the attachment title or attachment filename if it's empty or 'Auto Draft'.
     *
     * @param  int  $post_id
     * @param  WP_Post  $post
     *
     * @return void
     */
    public function populate_empty_title_on_save(int $post_id, WP_Post $post): void {
        if ($post->post_type !== 'document' || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (empty($post->post_title) || $post->post_title === 'Auto Draft') {
            $file_id = (int)get_post_meta($post_id, 'protected_document_file', true);
            if ($file_id) {
                wp_update_post([
                    'ID'         => $post_id,
                    'post_title' => get_the_title($file_id) ?? get_post_meta($file_id, '_wp_attached_file', true)
                ]);
            }
        }
    }

    /**
     * Always set documents to private status on save, unless they are drafts.
     * There should also be some CSS to hide other options for clarity
     * there is not filter to stop them rendering in the DOM at all)
     *
     * @param  $data
     * @param  $postarr
     *
     * @return array
     */
    public function always_set_documents_to_private($data, $postarr): array {
        if ($data['post_type'] === 'document' && !in_array($data['post_status'], ['draft', 'trash', 'auto-draft'], true)) {
            $data['post_status'] = 'private';
            $data['post_password'] = '';
        }

        return $data;
    }

}
