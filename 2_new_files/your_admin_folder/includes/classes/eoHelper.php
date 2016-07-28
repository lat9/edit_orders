<?php
// -----
// Part of the Edit Orders plugin (v4.1.6 and later).
//
class eoHelper extends base
{
    public function __construct ($orders_id)
    {
        $this->eo_action_level = EO_DEBUG_ACTION_LEVEL;

        // -----
        // Create the edit_orders directory, if not already present.
        //
        if ($this->eo_action_level != 0) {
            $log_file_dir = (defined ('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE) . '/edit_orders';
            if (!is_dir ($log_file_dir) && !mkdir ($log_file_dir, 0777, true)) {
                $this->eo_action_level = 0;
                trigger_error ("Failure creating the Edit Orders log-file directory ($log_file_dir); the plugin's debug is disabled until this issue is corrected.", E_USER_WARNING);
            } else {
                $this->logfile_name = $log_file_dir . '/debug_edit_orders_' . $orders_id . '.log';
            }
        }
    }
    
    public function eoLog ($message) {
        if ($this->eo_action_level != 0) {
            error_log ($message . PHP_EOL, 3, $this->logfile_name);
        }
    }
}