<?php
namespace Doubleedesign\SimpleDocumentPortal;
use WP_Query;

class FileHandler extends FileUploadHandler {

    public function __construct() {
        parent::__construct();

        // Redirect single file uploads to the protected directory
        add_filter('acf/upload_prefilter/name=protected_document_file', [$this, 'intercept_upload'], 10, 3);

        add_filter('acf/format_value/name=protected_document_file', [$this, 'format_custom_url'], 10, 3);
        add_filter('wp_get_attachment_url', [$this, 'persist_custom_url'], 10, 2);
        add_action('init', [$this, 'add_document_endpoint']);
        add_action('template_redirect', [$this, 'handle_document_endpoint']);
        add_action('pre_get_posts', [$this, 'filter_document_files_from_media_library']);

        // TODO: Delete file when post gets deleted
        // TODO: Delete file when "permanently delete" is called from the post edit modal
        // TODO: Delete leftover files when a document post is edited and the file field is changed
        // TODO: Ensure upload does not go into default location if there is a problem with the redirection
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
     * Ensures that the URL of an attachment is correct when fetched using `wp_get_attachment_url`.
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
     * Add rewrite rules so we can serve document files from WordPress while keeping them outside the web root.
     *
     * @return void
     */
    public function add_document_endpoint(): void {
        add_rewrite_rule('^documents/(.+)$', 'index.php?document=$matches[1]', 'top');
        add_rewrite_tag('%document%', '([^&]+)');
    }

    public function handle_document_endpoint(): void {
        $document = get_query_var('document');

        if ($document) {
            if (!$this->user_can_access_documents()) {
                if ($this->is_browser_request()) {
                    wp_redirect(home_url('/portal'), 302);
                    exit;
                }
                wp_send_json_error('Please log in to access resources', 401);
            }

            // Serve from protected directory outside web root
            $file_path = PluginEntrypoint::get_protected_directory() . '/' . sanitize_file_name($document);

            if (file_exists($file_path)) {
                header('Content-Type: ' . mime_content_type($file_path));
                readfile($file_path);
                exit;
            }
        }
    }

    /**
     * Utility function to check if the current user can access documents.
     *
     * @return bool
     */
    public function user_can_access_documents(): bool {
        return current_user_can('read_documents');
    }

    /**
     * Check if the request is likely from a browser.
     * This is a basic check based on the Accept header and User-Agent,
     * just so we can return a JSON response to requests from Postman or other non-browser tools
     * instead of the HTML page that users get in the browser.
     *
     * @return bool
     */
    private function is_browser_request(): bool {
        $accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check if accepts HTML (primary indicator)
        if (str_contains($accept_header, 'text/html')) {
            return true;
        }

        // Fallback to user agent check
        $browser_patterns = ['Mozilla', 'Chrome', 'Safari', 'Firefox', 'Edge'];
        foreach ($browser_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return true;
            }
        }

        return false;
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
            'post_type'  => 'document',
            'meta_query' => [
                [
                    'key'     => 'protected_document_file',
                    'value'   => $attachment_id,
                    'compare' => '=',
                ],
            ],
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);

        return !empty($query->posts);
    }

    /**
     * Do not show document files in the media library.
     *
     * @param  WP_Query  $query
     *
     * @return void
     */
    public function filter_document_files_from_media_library(WP_Query $query): void {
        if ($query->get('post_type') !== 'attachment') {
            return;
        }

        global $wpdb;
        $file_attachment_ids = $wpdb->get_col("
			SELECT meta_value FROM $wpdb->postmeta
			WHERE meta_key = 'protected_document_file'
		");

        $query->set('post__not_in', $file_attachment_ids);
    }
}
