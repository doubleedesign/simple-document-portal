<?php
namespace Doubleedesign\SimpleDocumentPortal;
use JetBrains\PhpStorm\NoReturn;
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
        add_action('init', [$this, 'add_document_endpoint']);
        add_action('template_redirect', [$this, 'handle_document_endpoint'], 20);
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
     * Add rewrite rules so we can serve document files from WordPress while keeping them outside the web root.
     *
     * @return void
     */
    public function add_document_endpoint(): void {
        add_rewrite_rule('^documents/(.+)$', 'index.php?document=$matches[1]', 'top');
        add_rewrite_tag('%document%', '([^&]+)');
    }

	/**
	 * Check if the referrer is a current site URL
	 * @return bool
	 */
	private function is_valid_referrer(): bool {
		$referrer = $_SERVER['HTTP_REFERER'] ?? '';
		$referrer_is_url = filter_var($referrer, FILTER_VALIDATE_URL) !== false;
		$referrer_is_internal = str_starts_with($referrer, get_site_url());

		return $referrer_is_url && $referrer_is_internal;
	}

	private function handle_document_endpoint_unauthorised(): void {
		$document = get_query_var('document');

		// Do not check just !document as it will return empty once and call everything again and give us a bad time
		if (!empty($document) && $this->is_valid_referrer()) {
			if(!$this->user_can_access_documents()) {
				if($this->is_browser_request()) {
					wp_redirect(home_url('/portal'), 302);
					exit;
				}
				wp_send_json_error('Please log in to access resources', 401);
			}
		}
	}

	private function handle_document_endpoint_file_not_found(): void {
		$document = get_query_var('document');
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

    public function handle_document_endpoint(): void {
		$this->handle_document_endpoint_unauthorised();
		$this->handle_document_endpoint_file_not_found();

        // Prepare to serve from protected directory outside web root
	    $document = get_query_var('document');
        $file_path = PluginEntrypoint::get_protected_directory() . '/' . sanitize_file_name($document);
        $mime_type = mime_content_type($file_path);

        if($mime_type === 'application/pdf' || $mime_type === 'text/pdf') {
	        $this->serve_pdf_through_browser($file_path, $mime_type);
        }
		else if(str_starts_with($mime_type, 'image/')) {
			$this->render_image_in_browser($file_path, $mime_type);
		}
		// No idea why reloading the Portal page was triggering this function, so I've just thrown in a check against "directory" to work around it
        else if($mime_type !== 'directory') {
			$this->serve_file_download($file_path, $mime_type);
        }
    }

	/**
	 * Use the browser's built-in PDF viewer or however else it is configured to handle PDFs by default
	 * @param $file_path
	 * @param $mime_type
	 * @return void
	 */
	#[NoReturn]
	private function serve_pdf_through_browser($file_path, $mime_type): void {
		header('HTTP/1.1 200 OK');
		header('Content-Type: ' . $mime_type);
		header('Content-Description: File Transfer');
		header('Content-Disposition: inline; filename="'.basename($file_path).'"');
		header('Cache-Control: must-revalidate');
		header('Content-Transfer-Encoding: binary');
		readfile($file_path);
	}

	private function render_image_in_browser($file_path, $mime_type): void {
		header('HTTP/1.1 200 OK');
		header('Content-Type: ' . $mime_type);
		header('Content-Disposition: inline; filename="'.basename($file_path).'"');
		header('Cache-Control: must-revalidate');
		header('Content-Transfer-Encoding: binary');
		readfile($file_path);
	}

	#[NoReturn]
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
