<?php
namespace Doubleedesign\SimpleDocumentPortal;
use RuntimeException;

abstract class FileUploadHandler {

    public function __construct() {}

    public function intercept_upload($errors, $file, $field) {
        add_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10, 1);

        return $errors;
    }

    public function redirect_to_protected_dir($upload_dir_array): array {
        $protected_dir = PluginEntrypoint::get_protected_directory();
        if (!is_dir($protected_dir)) {
            throw new RuntimeException(sprintf('Directory for document portal expected at "%s" does not exist', $protected_dir));
        }

        return [
            'path'    => $protected_dir,
            'url'     => get_site_url() . '/documents',
            'subdir'  => '',
            'basedir' => $protected_dir,
            'baseurl' => get_site_url() . '/documents',
            'error'   => false,
        ];
    }

    /**
     * Delete a file from the custom directory, and its metadata from the database.
     *
     * @param  int  $attachment_id
     *
     * @return bool
     */
    protected function delete_file(int $attachment_id): bool {
        // Temporarily change where the internal WordPress functions look for the file,
        // so we can let WordPress handle the deletion like any other file
        add_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10, 1);

        $result = wp_delete_attachment($attachment_id);

        // Remove the filter to avoid affecting other uploads
        remove_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10);

        return $result !== null && $result !== false;
    }
}
