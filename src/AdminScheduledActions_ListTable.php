<?php
namespace Doubleedesign\SimpleDocumentPortal;
use ActionScheduler;
use ActionScheduler_Abstract_ListTable;
use ActionScheduler_ListTable;
use ActionScheduler_Store;
use Exception;

/**
 * Customised version of the Action Scheduler List Table for the Documents admin area.
 */
class AdminScheduledActions_ListTable extends ActionScheduler_ListTable {

    public function __construct() {
        parent::__construct(ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner());

        $this->table_header = __('Automated Actions', 'simple-document-portal');
        // Do not allow deleting actions from this screen
        $this->bulk_actions = [];
        // Remove the 'cancel' action from the row actions
        // Note: Actions could still be cancelled from the main Action Scheduler screen
        $this->row_actions['hook'] = array_filter(
            $this->row_actions['hook'],
            function($action) {
                return $action !== 'cancel';
            },
            ARRAY_FILTER_USE_KEY
        );
        // Remove irrelevant columns from the table for simplicity
        $this->columns = array_filter(
            $this->columns,
            function($key) {
                return !in_array($key, ['args', 'group'], true);
            },
            ARRAY_FILTER_USE_KEY
        );
        // Set which columns can be used for sorting
        $this->sort_by = ['status', 'schedule'];
    }

    /**
     * Make the status column not sortable in the UI table,
     * while still keeping it functionally sortable in queries and such
     * because we have custom sorting for it that is modified at the SQL query level
     *
     * @return array
     */
    public function get_sortable_columns(): array {
        return array_filter(
            parent::get_sortable_columns(),
            function($key) {
                // Remove 'group' from the sortable columns, as it is not relevant for this view
                return $key !== 'status';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Prepare the items for display in the admin table.
     * Because this only prepares the current page of items, filtering and sorting the parent class's result ($this->items) doesn't work well,
     * so much of that is duplicated here and then customised.
     */
    public function prepare_items(): void {
        if (empty($this->store)) {
            return;
        }

        $this->prepare_column_headers();

        $per_page = $this->get_items_per_page($this->get_per_page_option_name(), $this->items_per_page);

        $query = array(
            'per_page' => $per_page,
            'offset'   => $this->get_items_offset(),
            'status'   => $this->get_request_status(),
            'orderby'  => $this->get_request_orderby(),
            'order'    => (empty($_GET['order']) || $this->get_request_orderby() === 'status') ? 'DESC' : $this->get_request_order(),
            'search'   => $this->get_request_search_query(),
            'group'    => 'simple-document-portal'
        );

        /**
         * Change query arguments to query for past-due actions.
         * Past-due actions have the 'pending' status and are in the past.
         * This is needed because registering 'past-due' as a status is overkill.
         */
        if ($this->get_request_status() === 'past-due') {
            $query['status'] = ActionScheduler_Store::STATUS_PENDING;
            $query['date'] = as_get_datetime_object();
        }

        /**
         * Custom: Change query arguments for incomplete actions.
         * This is a pseudo-status that includes all actions that are not complete or cancelled
         * (i.e., pending, running, failed, or past-due).
         */
        if ($this->get_request_status() === 'incomplete') {
            $query['status'] = array(
                ActionScheduler_Store::STATUS_RUNNING,
                ActionScheduler_Store::STATUS_PENDING,
                ActionScheduler_Store::STATUS_FAILED
            );
        }

        $this->items = array();

        $total_items = $this->store->query_actions($query, 'count');

        $status_labels = $this->store->get_status_labels();

        foreach ($this->store->query_actions($query) as $action_id) {
            try {
                $action = $this->store->fetch_action($action_id);
            }
            catch (Exception $e) {
                continue;
            }
            if (is_a($action, 'ActionScheduler_NullAction')) {
                continue;
            }
            $this->items[$action_id] = array(
                'ID'          => $action_id,
                'hook'        => $action->get_hook(),
                'status_name' => $this->store->get_status($action_id),
                'status'      => $status_labels[$this->store->get_status($action_id)],
                'log_entries' => $this->logger->get_logs($action_id),
                'claim_id'    => $this->store->get_claim_id($action_id),
                'recurrence'  => $this->get_recurrence($action),
                'schedule'    => $action->get_schedule(),
            );
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page),
            )
        );
    }

    /**
     * Get all the action counts, not just the current page ($this->items is just the current page).
     * This is copied from \ActionScheduler_DBStore::action_counts and adapted to filter to only actions for this plugin,
     * and to add the link and count for an "incomplete" filter (the actual filtering is done in prepare_items)
     *
     * @return void
     */
    protected function set_action_counts(): void {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT aa.status, COUNT(*) as count 
			         FROM {$wpdb->actionscheduler_actions} aa
			         INNER JOIN {$wpdb->actionscheduler_groups} ag ON aa.group_id = ag.group_id 
			         WHERE ag.slug = %s
			         GROUP BY aa.status",
            'simple-document-portal'
        );

        $actions_count_by_status = array();
        $statuses_and_labels = ActionScheduler::store()->get_status_labels();

        foreach ($wpdb->get_results($sql) as $action_data) {
            // Ignore any actions with invalid status.
            if (array_key_exists($action_data->status, $statuses_and_labels)) {
                $actions_count_by_status[$action_data->status] = $action_data->count;
            }
        }

        $all_items_count = array_sum($actions_count_by_status);
        $complete_count = $actions_count_by_status[ActionScheduler_Store::STATUS_COMPLETE];

        $this->status_counts = [
            'incomplete' => $all_items_count - $complete_count,
            ...$actions_count_by_status
        ];
    }

    protected function display_filter_by_status(): void {
        $this->set_action_counts();

        // The parent class resets the counts within this method,
        // so we need to skip that and call the grandparent method
        // to use its rendering implementation with what we just set for the counts
        // (otherwise we'd have to copy it)
        ActionScheduler_Abstract_ListTable::display_filter_by_status();
    }

    public function display_page(): void {
        $this->prepare_items();

        echo '<div class="wrap">';
        $this->display_header();
        $this->display_admin_notices();
        $this->display_filter_by_status();
        $this->display_table();
        echo '</div>';
    }

}
