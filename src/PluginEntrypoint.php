<?php
namespace Doubleedesign\SimpleDocumentPortal;
use Exception;

/**
 * The main entry point for the Simple Document Portal plugin.
 * TODO: Implement uninstall steps
 */
class PluginEntrypoint {
    private static string $directory = '';

    public function __construct() {
        new Documents();
        new Folders();
        new FileHandler();
        new ScheduledActions();

        if (is_admin()) {
            new DocumentsAdmin();
            new FoldersAdmin();
            new AdminUI();
            new AdminSettings();
            new BulkUploader();
        }
        if (!is_admin()) {
            new TemplateHandler();
        }
    }

    public static function activate(): void {
        try {
            self::create_protected_directory();
            flush_rewrite_rules();
            UserRoles::create_roles();
            UserRoles::reassign_users_roles();
            UserPermissions::map_permissions_to_existing_roles();
        }
        catch (Exception $e) {
            self::deactivate();
            wp_die(
                esc_html__('Failed to activate Simple Document Portal: ', 'simple-document-portal') . esc_html($e->getMessage()),
                'Plugin Activation Error',
                array('back_link' => true)
            );
        }
    }

    public static function deactivate(): void {
        UserRoles::delete_roles();
        UserPermissions::reset_default_capabilities();

        // Remove stored directory location from options table
        delete_option('simple_document_portal_directory');
    }

    public static function uninstall(): void {
        UserRoles::revert_users_roles(true);
        // TODO Clear warning and confirmation that uninstalling will delete the documents
        // TODO Implement deletion
    }

    /**
     * @throws Exception
     */
    protected static function create_protected_directory(): void {
        // Go up a level from the web root and return the full system path
        $dir = dirname(WP_CONTENT_DIR, 2) . '/documents';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }

        if (!file_exists($dir) || !is_writable($dir)) {
            throw new Exception('The documents directory was not able to be created, or is not writable. It should exist one level above the web root (for example, for cPanel hosting this usually means the same level as the `public_html` folder). Please create it manually or update its permissions, and try activating the plugin again.');
        }

        $index_content = <<<PHP
			<?php
			// Directory for documents for Simple Document Portal
			// Redirect if accessed directly
			if (!defined('ABSPATH')) {
				header("Location: https://{$_SERVER['HTTP_HOST']}/portal");
			}
		PHP;

        file_put_contents($dir . '/index.php', $index_content);
        self::$directory = $dir;

        // Save location in options table so the admin settings page can access it
        update_option('simple_document_portal_directory', self::$directory);
    }

    public static function get_protected_directory(): string {
        if (empty(self::$directory)) {
            self::$directory = get_option('simple_document_portal_directory', '');
        }

        return self::$directory;
    }
}
