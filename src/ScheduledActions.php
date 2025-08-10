<?php
namespace Doubleedesign\SimpleDocumentPortal;

class ScheduledActions extends FileUploadHandler {

    public function __construct() {
        parent::__construct();
        add_action('simple_document_portal_scheduled_cleanup', [$this, 'delete_orphaned_files'], 10, 0);
        add_action('plugins_loaded', [$this, 'initialise_custom_admin_view'], 10);
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
        \Symfony\Component\VarDumper\VarDumper::dump('action run');
    }
}
