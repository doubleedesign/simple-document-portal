<?php
namespace Doubleedesign\SimpleDocumentPortal;
use ActionScheduler_AdminView;

class ScheduledActionsAdminView extends ActionScheduler_AdminView {

    public function __construct() {
        parent::init();

        // Hacky fix for the menu item not appearing in the correct order, presumably because of the order of hooks for this vs ACF admin options pages.
        add_action('admin_menu', function() {
            global $submenu;
            // TODO: This can probably be replaced with array_find() when we can require PHP 8.4+
            $item = array_filter(
                $submenu['edit.php?post_type=document'] ?? [],
                function($item) {
                    return $item[2] === 'scheduled-actions';
                }
            );
            if (!empty($item)) {
                // Move the Automated Actions submenu item to the end of the Document post type submenu.
                $index = array_key_first($item);
                array_push($submenu['edit.php?post_type=document'], array_values($item)[0]);
                unset($submenu['edit.php?post_type=document'][$index]);
            }
        }, 100);
    }

    public function register_menu(): void {
        $hook_suffix = add_submenu_page(
            'edit.php?post_type=document',
            __('Automated Actions', 'simple-document-portal'),
            __('Automated Actions', 'simple-document-portal'),
            'manage_documents_options',
            'scheduled-actions',
            [$this, 'render_admin_ui'],
            50
        );

        // Process actions if arriving with the appropriate URL params (e.g., if "Run" is clicked and the page reloads)
        add_action('load-' . $hook_suffix, array($this, 'process_admin_ui'));
    }

    public function render_admin_ui(): void {
        $table = new AdminScheduledActions_ListTable();
        $table->display_page();
    }
}
