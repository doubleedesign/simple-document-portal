<?php
namespace Doubleedesign\SimpleDocumentPortal;
use WP_Query;
use WP_Post;

class FileHandler extends FileUploadHandler {

    public function __construct() {
        parent::__construct();

        // Redirect single file uploads to the protected directory
        add_filter('acf/upload_prefilter/name=protected_document_file', [$this, 'intercept_upload'], 10, 3);

        add_filter('acf/format_value/name=protected_document_file', [$this, 'format_custom_url'], 10, 3);
        add_filter('wp_get_attachment_url', [$this, 'persist_custom_url'], 10, 2);
        add_filter('get_attached_file', [$this, 'persist_custom_path'], 10, 2);
        add_action('pre_get_posts', [$this, 'filter_document_files_from_media_library']);

        add_action('update_post_meta', [$this, 'delete_old_file_on_document_save'], 10, 4);
        add_action('before_delete_post', [$this, 'delete_file_on_permanent_delete_of_document'], 10, 1);
        add_action('deleted_post', [$this, 'delete_file_on_permanent_delete_of_attachment'], 10, 2);
    }

    /**
     * Ensure ACF's formatting functions return the correct URL for a protected document file.
     *
     * @param  $value
     * @param  $post_id
     * @param  $field
     *
     * @return mixed
     */
    public function format_custom_url($value, $post_id, $field): mixed {
        if (get_post_type($post_id) !== 'document') {
            return $value;
        }

        if ($field['name'] !== 'protected_document_file') {
            return $value;
        }

        if ($value && is_array($value) && isset($value['url'])) {
            $expected_path = '/documents/';
            if (!str_contains($value['url'], $expected_path)) {
                $filename = basename($value['url']);
                $value['url'] = 'documents/' . $filename;
            }
        }

        return $value;
    }

    /**
     * Ensures that the URL of an attachment is correct when fetched using wp_get_attachment_url().
     *
     * @param  string  $url
     * @param  int  $attachment_id
     *
     * @return string
     */
    public function persist_custom_url(string $url, int $attachment_id): string {
        if (!$this->is_attached_to_document($attachment_id)) {
            return $url;
        }

        $expected_path = '/documents/';
        if (!str_contains($url, $expected_path)) {
            $filename = basename($url);
            $url = '/documents/' . $filename;
        }

        return $url;
    }

    /**
     * Ensure the absolute path to the file is correct when fetched using get_attached_file().
     * Important for deleting the file (and thumbnails for images) when the attachment is deleted.
     *
     * @param  string  $path
     * @param  int  $attachment_id
     *
     * @return string
     */
    public function persist_custom_path(string $path, int $attachment_id): string {
        if (!$this->is_attached_to_document($attachment_id)) {
            return $path;
        }

        // Filtering the upload directory temporarily is required for when this is called on get_attached_file()
        // by WordPress's attachment deletion functions
        add_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10, 1);

        $expected_path = '/documents/';
        if (!str_contains($path, $expected_path)) {
            $filename = basename($path);
            $path = PluginEntrypoint::get_protected_directory() . DIRECTORY_SEPARATOR . $filename;
        }

        remove_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10);

        return $path;
    }

    /**
     * Utility function to check if an attachment is attached to a document post.
     *
     * @param  int  $attachment_id
     *
     * @return bool
     */
    public function is_attached_to_document(int $attachment_id): bool {
        $query = new WP_Query([
            'post_type'      => 'document',
            'meta_query'     => [
                [
                    'key'     => 'protected_document_file',
                    'value'   => $attachment_id,
                    'compare' => '=',
                ],
            ],
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);

        $attachment_post = get_post($attachment_id);

        return !empty($query->posts) || ($attachment_post->post_parent && get_post_type($attachment_post->post_parent) === 'document');
    }

    /**
     * Do not show document files in the media library.
     *
     * @param  $query
     *
     * @return void
     */
    public function filter_document_files_from_media_library($query): void {
        if ($query->get('post_type') !== 'attachment') {
            return;
        }

        // If we are currently editing a document post, bail so we get the default ACF behaviour
        // (set to show files uploaded to that document post at the time of writing)
        if ($query->query_vars['post_parent'] && get_post_type($query->query_vars['post_parent']) === 'document') {
            return;
        }

        global $wpdb;
        $file_attachment_ids = $wpdb->get_col("
			SELECT meta_value FROM $wpdb->postmeta
			WHERE meta_key = 'protected_document_file'
		");

        $query->set('post__not_in', $file_attachment_ids);
    }

    /**
     * If an admin edits a document post and changes the file,
     * delete the old file when the document post is saved.
     *
     * @param  $meta_id
     * @param  $post_id
     * @param  $meta_key
     * @param  $meta_value
     *
     * @return bool
     */
    public function delete_old_file_on_document_save($meta_id, $post_id, $meta_key, $meta_value): bool {
        if (get_post_type($post_id) !== 'document' || $meta_key !== 'protected_document_file') {
            return false;
        }

        $old_attachment_id = (int)get_post_meta($post_id, 'protected_document_file', true);
        $new_attachment_id = (int)$meta_value;
        if ($old_attachment_id === $new_attachment_id) {
            // No change in file, nothing to delete
            return false;
        }

        return $this->delete_file($old_attachment_id);
    }

    /**
     * Delete the file when a document post is permanently deleted.
     * This allows admins to recover documents from the trash without losing the file.
     *
     * @param  $post_id
     *
     * @return bool
     */
    public function delete_file_on_permanent_delete_of_document($post_id): bool {
        if (get_post_type($post_id) !== 'document') {
            return false;
        }

        $attachment_id = (int)get_post_meta($post_id, 'protected_document_file', true);

        return $this->delete_file($attachment_id);
    }

    /**
     * Delete file when "permanently delete" is called from the media library modal or anything else that calls wp_delete_attachment().
     * The deleted_post hook runs within wp_delete_attachment().
     * We can't hook into anything in the delete_post action to get in first
     * because it checks for the attachment post type and redirects to wp_delete_attachment early.
     *
     * Note: I have no idea why at the time of writing, but this seems necessary for PDFs but not images. Not sure about other file types.
     *
     * @param  int  $post_id
     * @param  WP_Post  $post
     *
     * @return void
     */
    public function delete_file_on_permanent_delete_of_attachment(int $post_id, WP_Post $post): void {
        if ($post->post_type !== 'attachment' || !$this->is_attached_to_document($post_id)) {
            return;
        }

        // This runs after wp_delete_attachment() has already deleted the data, so our delete_file() method won't work here.
        // We also can't run this on the delete_attachment hook because that runs within wp_delete_attachment(),
        // causing an infinite loop if we were to call wp_delete_attachment() (as $this->delete_file() does).
        // wp_delete_attachment_files() is called *after* the deleted_post hook, so we just need to tell it where to look for the file,
        // assuming we have already fixed the path using a filter for get_attached_file.
        add_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10, 1);
    }
}
