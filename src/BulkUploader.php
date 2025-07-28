<?php

namespace Doubleedesign\SimpleDocumentPortal;

class BulkUploader extends FileUploadHandler {

    public function __construct() {
        parent::__construct();

        // Redirect bulk file uploads to the protected directory
        add_filter('acf/upload_prefilter/name=document_files_to_upload', [$this, 'intercept_upload'], 10, 3);

        add_action('acf/init', [$this, 'create_bulk_upload_screen'], 10);
        add_action('acf/include_fields', [$this, 'add_bulk_upload_fields'], 10, 0);
        add_action('gettext', [$this, 'update_button_text'], 10, 2);
        add_action('acf/save_post', [$this, 'run_bulk_upload'], 10, 1);
    }

    public function create_bulk_upload_screen(): void {
        acf_add_options_page(array(
            'page_title'      => __('Add Multiple Documents', 'simple-document-portal'),
            'menu_title'      => __('Bulk Upload', 'simple-document-portal'),
            'parent_slug'     => 'edit.php?post_type=document',
            'menu_slug'       => 'bulk-upload',
            'capability'      => 'manage_documents_options',
            'redirect'        => false,
            'update_button'   => __('Save documents', 'acf'),
            'updated_message' => __('Documents created.', 'acf'),
        ));
    }

    public function add_bulk_upload_fields(): void {
        acf_add_local_field_group(array(
            'key'                   => 'group_documents-bulk-upload',
            'title'                 => 'Bulk upload',
            'fields'                => array(
                array(
                    'key'               => 'field_document_folder',
                    'label'             => 'Folder',
                    'name'              => 'add_to_folder',
                    'aria-label'        => '',
                    'type'              => 'taxonomy',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'taxonomy'             => 'folder',
                    'add_term'             => 0,
                    'save_terms'           => 0,
                    'load_terms'           => 0,
                    'return_format'        => 'id',
                    'field_type'           => 'select',
                    'allow_null'           => true,
                    'allow_in_bindings'    => 0,
                    'bidirectional'        => 0,
                    'multiple'             => 0,
                    'bidirectional_target' => []
                ),
                array(
                    'key'               => 'field_document_files_to_upload',
                    'label'             => 'Files',
                    'name'              => 'document_files_to_upload',
                    'aria-label'        => '',
                    'type'              => 'gallery',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'return_format'     => 'array',
                    'library'           => 'uploadedTo',
                    'min'               => '',
                    'max'               => '',
                    'min_width'         => '',
                    'min_height'        => '',
                    'min_size'          => '',
                    'max_width'         => '',
                    'max_height'        => '',
                    'max_size'          => '',
                    'mime_types'        => '',
                    'insert'            => 'append',
                    'preview_size'      => 'medium',
                ),
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'bulk-upload',
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

    public function update_button_text($translation, $text): ?string {
        // Check if we are on the bulk upload screen
        if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'bulk-upload') {
            return $translation;
        }

        if ($text === 'Add to gallery') {
            return __('Add documents', 'simple-document-portal');
        }

        return $translation;
    }

    public function run_bulk_upload($post_id): void {
        // Ensure we are only responding to the bulk upload POST request
        // Note: We can't do a negative check and return to bail early here, because that will bail early for other options pages
        if (is_admin() && ($post_id === 'options' && isset($_POST['acf']['field_document_files_to_upload']))) {
            $file_ids = $_POST['acf']['field_document_files_to_upload'];
            if (is_array($file_ids)) {
                $folder_id = isset($_POST['acf']['field_document_folder']) ? (int)$_POST['acf']['field_document_folder'] : null;
                $this->create_document_posts($file_ids, $folder_id);
            }

            // Clear the uploader (gallery) field value after processing, so the uploaded files are not processed again
            update_field('field_document_files_to_upload', [], 'options');
        }
    }

    public function create_document_posts(array $file_ids, ?int $folder_id): void {
        foreach ($file_ids as $file_id) {
            // At this point, the file has been uploaded and an attachment post exists for it
            $file = get_post($file_id);

            // Insert a document post corresponding to the uploaded file
            $post_data = array(
                'post_title'   => sanitize_file_name($file->post_title),
                'post_content' => '',
                'post_status'  => 'private',
                'post_type'    => 'document',
            );
            $post_id = wp_insert_post($post_data);

            // Add the file's attachment ID as the protected_document_file meta field value
            update_post_meta($post_id, 'protected_document_file', $file_id);

            // Assign the document to the specified folder (taxonomy term) if provided
            if ($folder_id) {
                wp_set_object_terms($post_id, $folder_id, 'folder', true);
            }
        }
    }

    public function handle_abandoned_upload(): void {
        // TODO: Handle options page update being abandoned (files are uploaded but not processed into document posts - they need to be deleted)
        // Maybe a scheduled action can handle this?
    }
}
