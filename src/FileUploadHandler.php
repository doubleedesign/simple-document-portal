<?php
namespace Doubleedesign\SimpleDocumentPortal;
use RuntimeException;

abstract class FileUploadHandler {

	public function __construct() {
	}

	function intercept_upload($errors, $file, $field) {
		add_filter('upload_dir', [$this, 'redirect_to_protected_dir'], 10, 1);

		return $errors;
	}

	function redirect_to_protected_dir($upload_dir_array): array {
		$protected_dir = PluginEntrypoint::get_protected_directory();
		if (!is_dir($protected_dir)) {
			throw new RuntimeException(sprintf('Directory for document portal expected at "%s" does not exist', $protected_dir));
		}

		return [
			'path' => $protected_dir,
			'url' => '/documents',
			'subdir' => '',
			'basedir' => $protected_dir,
			'baseurl' => '/documents',
			'error' => false,
		];
	}
}
