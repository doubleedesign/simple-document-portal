<?php
namespace Doubleedesign\SimpleDocumentPortal;

class ScheduledActions extends FileUploadHandler {

    public function __construct() {
        parent::__construct();
        add_action('plugins_loaded', [$this, 'initialise_custom_admin_view'], 10);
        add_action('admin_notices', [$this, 'missing_actions_warning'], 10);
        add_action('simple_document_portal_scheduled_cleanup', [$this, 'delete_orphaned_files'], 10, 0);
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

    public function missing_actions_warning(): void {
        if (!as_next_scheduled_action('simple_document_portal_scheduled_cleanup')) {
            $heading = __('Missing cleanup action for Simple Document Portal', 'simple-document-portal');
            $messageLine1 = esc_html('This action periodically finds files not currently associated with a document and deletes them (this can occur due to scenarios where there is efficient way to trigger a deletion at the time of disassociation, such as abandoned bulk uploads).');
            $messageLine2 = esc_html('Without this action, documents might be retained on the server that are not in use on the website.');

            echo <<<HTML
			<div class="notice notice-warning">
				<h2 style="font-size:1em">$heading</h2>
				<p>$messageLine1</p>
				<p>$messageLine2</p>
			</div>
			HTML;
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

        if (wp_get_environment_type() === 'local' && function_exists('dump')) {
            dump([
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
