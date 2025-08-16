<?php
namespace Doubleedesign\SimpleDocumentPortal;
use ActionScheduler_DBStore;

class AdminScheduledActions_Store extends ActionScheduler_DBStore {

    protected function get_query_actions_sql(array $query, $select_or_count = 'select'): string {

        if ($query['orderby'] === 'status' && empty($query['status'])) {
            // The easiest way to skip the parent's orderby is to pretend we're doing a 'count' not a 'select'
            // to get the relevant part of the default SQL only.
            // (those are the two valid options, and the orderby is only applied to select)
            $temp = parent::get_query_actions_sql($query, 'count');
            // Then we do a basic text replacement to update it back to getting the IDs, not the count of them
            $temp = str_replace('SELECT count(a.action_id)', 'SELECT a.action_id', $temp);

            // Then we can add our custom orderby clause
            $updated = $temp . " ORDER BY FIELD(a.status, 'pending', 'failed', 'complete', 'canceled')";

            return $updated;
        }

        return parent::get_query_actions_sql($query, $select_or_count);
    }
}
