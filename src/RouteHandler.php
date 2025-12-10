<?php
namespace Doubleedesign\SimpleDocumentPortal;

class RouteHandler {

    public function __construct() {
        add_action('init', [$this, 'add_document_endpoint']);
        add_action('template_redirect', [$this, 'handle_document_endpoint'], 20);
    }

    public function add_document_endpoint(): void {
        add_rewrite_rule('^documents/(.+)$', 'index.php?document=$matches[1]', 'top');
        add_rewrite_tag('%document%', '([^&]+)');
    }

    /**
     * Add rewrite rules so we can serve document files from WordPress while keeping them outside the web root.
     *
     * @return void
     */
    public function handle_document_endpoint(): void {
        $query_var = get_query_var('document');
        if (empty($query_var)) {
            return;
        }

        $this->handle_document_endpoint_unauthorised();
        $this->handle_document_endpoint_file_not_found();
        $this->handle_document_id_or_slug_path();

        // Prepare to serve from protected directory outside web root
        $document = $this->get_filename_from_document_query_var();
        $file_path = PluginEntrypoint::get_protected_directory() . '/' . sanitize_file_name($document);
        $mime_type = mime_content_type($file_path);

        if ($mime_type === 'application/pdf' || $mime_type === 'text/pdf') {
            $this->serve_pdf_through_browser($file_path, $mime_type);
        }
        else if (str_starts_with($mime_type, 'image/')) {
            $this->render_image_in_browser($file_path, $mime_type);
        }
        // No idea why reloading the Portal page was triggering this function, so I've just thrown in a check against "directory" to work around it
        else if ($mime_type !== 'directory') {
            $this->serve_file_download($file_path, $mime_type);
        }
    }

    /**
     * Check if the referrer is a current site URL
     *
     * @return bool
     */
    private function is_valid_referrer(): bool {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $referrer_is_url = filter_var($referrer, FILTER_VALIDATE_URL) !== false;
        $referrer_is_internal = str_starts_with($referrer, get_site_url());

        return $referrer_is_url && $referrer_is_internal;
    }

    private function get_filename_from_document_query_var(): string {
        $query_var = get_query_var('document');
        $post_id = null;

        // Handle presumed post IDs (where the query var is a number)
        if (is_numeric($query_var)) {
            $post_id = (int)$query_var;
        }

        // Then check if it is a post slug
        $document_post = get_page_by_path($query_var, OBJECT, 'document');
        if ($document_post instanceof \WP_Post) {
            $post_id = $document_post->ID;
        }

        // If a post ID was found, get the attached file's name and return that instead
        if ($post_id) {
            $attachment_id = (int)get_post_meta($post_id, 'protected_document_file', true);
            $document_file = get_attached_file($attachment_id);

            return basename($document_file);
        }

        return $query_var;
    }

    private function handle_document_endpoint_unauthorised(): void {
        $document = $this->get_filename_from_document_query_var();

        if (!$this->is_valid_referrer() && !is_user_logged_in()) {
            $this->redirect_to_login();
        }

        // Do not check just !document as it will return empty once and call everything again and give us a bad time
        if (!empty($document) && $this->is_valid_referrer()) {
            if (!$this->user_can_access_documents()) {
                $this->redirect_to_login();
            }
        }
    }

    private function redirect_to_login(): void {
        if ($this->is_browser_request()) {
            wp_redirect(home_url('/portal'), 302);
            exit;
        }
        wp_send_json_error('Please log in to access resources', 401);
    }

    private function handle_document_endpoint_file_not_found(): void {
        $document = $this->get_filename_from_document_query_var();
        $file_path = PluginEntrypoint::get_protected_directory() . '/' . sanitize_file_name($document);

        if (!file_exists($file_path)) {
            status_header(404);
            if ($this->is_browser_request()) {
                wp_redirect(home_url('/404'), 302);
                exit;
            }
            else {
                wp_send_json_error('File not found', 404);
            }
        }
    }

    /**
     * If a document ID is given instead of a filename, redirect to the correct URL with the filename
     *
     * @return void
     */
    private function handle_document_id_or_slug_path(): void {
        $query_var = get_query_var('document');
        if (empty($query_var)) {
            return;
        }

        $filename = $this->get_filename_from_document_query_var();
        if ($filename === $query_var) {
            return; // Already the filename, no need to redirect
        }

        $correct_url = home_url('/documents/' . $filename);
        wp_redirect($correct_url, 301);
        exit;
    }

    /**
     * Use the browser's built-in PDF viewer or however else it is configured to handle PDFs by default
     *
     * @param  $file_path
     * @param  $mime_type
     *
     * @return void
     */
    private function serve_pdf_through_browser($file_path, $mime_type): void {
        header('HTTP/1.1 200 OK');
        header('Content-Type: ' . $mime_type);
        header('Content-Description: File Transfer');
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Cache-Control: must-revalidate');
        header('Content-Transfer-Encoding: binary');
        readfile($file_path);
    }

    private function render_image_in_browser($file_path, $mime_type): void {
        header('HTTP/1.1 200 OK');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Cache-Control: must-revalidate');
        header('Content-Transfer-Encoding: binary');
        readfile($file_path);
    }

    private function serve_file_download($file_path, $mime_type): void {
        header('HTTP/1.1 200 OK');
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }

        readfile($file_path);
        exit;
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
}
