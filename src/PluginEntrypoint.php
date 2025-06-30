<?php
namespace Doubleedesign\SimpleDocumentPortal;
use Exception;

/**
 * The main entry point for the Simple Document Portal plugin.
 * TODO: Implement uninstall steps
 * TODO: Settings link in plugin list
 */
class PluginEntrypoint {
	private static string $directory = '';

	public function __construct() {
		new Documents();
		new FileHandler();
		new ScheduledActions();

		if(is_admin()) {
			new AdminUI();
			new AdminSettings();
			new BulkUploader();
		}
		if(!is_admin()) {
			new TemplateHandler();
		}
	}

	public static function activate(): void {
		self::create_protected_directory();
		flush_rewrite_rules();
		UserPermissions::map_permissions_to_existing_roles();
	}

	public static function deactivate(): void {
		UserPermissions::reset_default_capabilities();

		// Remove stored directory location from options table
		delete_option('simple_document_portal_directory');
	}

	public static function uninstall() {
		// TODO Clear warning and confirmation that uninstalling will delete the documents
		// TODO Implement deletion
	}

	protected static function create_protected_directory(): void {
		$index_content = <<<PHP
			<?php
			// Directory for documents for Simple Document Portal
			// Redirect if accessed directly
			if (!defined('ABSPATH')) {
				header("Location: https://{$_SERVER['HTTP_HOST']}/portal");
			}
		PHP;

		try {
			// Go up a level from the web root and return the full system path
			$dir = dirname(WP_CONTENT_DIR, 2) . '/documents';
			if(!file_exists($dir)) {
				wp_mkdir_p($dir);
			}

			file_put_contents($dir . '/index.php', $index_content);
			self::$directory = $dir;

			// Save location in options table so the admin settings page can access it
			update_option('simple_document_portal_directory', self::$directory);
		}
		catch (Exception $e) {
			try {
				$alternateDir = WP_CONTENT_DIR . '/uploads/documents';
				self::$directory = $alternateDir;

				// Save location in options table so the admin settings page can access it
				update_option('simple_document_portal_directory', self::$directory);

				// TODO: Warn about protecting this directory
			}
			catch (Exception $e) {
				// TODO: Display an admin notice
				error_log('Failed to create protected directory for Simple Document Portal: ' . $e->getMessage());
				self::deactivate();
			}
		}
	}

	public static function get_protected_directory(): string {
		if(empty(self::$directory)) {
			self::$directory = get_option('simple_document_portal_directory', '');
		}

		return self::$directory;
	}
}
