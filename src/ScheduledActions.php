<?php
namespace Doubleedesign\SimpleDocumentPortal;

class ScheduledActions extends FileUploadHandler {

    public function __construct() {
        parent::__construct();
        add_action('plugins_loaded', [$this, 'initialise_custom_admin_view'], 10);
        add_action('simple_document_portal_scheduled_cleanup', [$this, 'delete_orphaned_files'], 10, 0);

        // TODO Add an admin notice if the expected actions have been disabled or deleted
        // because while the custom UI is simplified and designed to prevent this, the main Actions Scheduler screen could still be used.
    }

    public function initialise_custom_admin_view(): void {
        if (is_admin()) {
            if (class_exists('ActionScheduler_AdminView')) {
                new ScheduledActionsAdminView();
            }
            else {
                error_log('ActionScheduler_AdminView class not found. You may need to adjust when ScheduledActionsAdminView is loaded.');
            }
        }
    }

    public function delete_orphaned_files(): void {
        $orphaned_attachments = $this->find_orphaned_attachments();
        if (empty($orphaned_attachments)) {
            return;
        }

        $successes = [];
        $failures = [];
        foreach ($orphaned_attachments as $attachment) {
            $success = $this->delete_file($attachment->id);
            if ($success) {
                array_push($successes, $attachment);
            }
            else {
                array_push($failures, $attachment);
            }
        }

        if (wp_get_environment_type() === 'local') {
            \Symfony\Component\VarDumper\VarDumper::dump([
                'orphaned_attachments' => $orphaned_attachments,
                'successes'            => $successes,
                'failures'             => $failures,
            ]);
        }
        // TODO log the result somewhere visible to admins
    }

    private function find_orphaned_attachments(): array {
        global $wpdb;

        $dir = dirname(WP_CONTENT_DIR, 2);
        $full_dir_path = PluginEntrypoint::get_protected_directory();
        $short_dir_path = str_replace($dir, '', $full_dir_path);

        $sql = $wpdb->prepare(
            "SELECT id, guid
				FROM {$wpdb->posts} AS wp_posts
				LEFT JOIN {$wpdb->postmeta} as wp_postmeta ON wp_posts.id = wp_postmeta.meta_value
				  AND wp_postmeta.meta_key = 'protected_document_file'
				WHERE wp_posts.post_type = %s
				  AND wp_posts.guid LIKE %s
				  AND wp_postmeta.meta_value IS NULL",
            'attachment',
            "%$short_dir_path/%"
        );

        $result = $wpdb->get_results($sql);

        return $result ?? [];
    }
}
