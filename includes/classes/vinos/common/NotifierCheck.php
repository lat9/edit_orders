<?php
namespace Vinos\Common;

// -----
// Common class used by various plugins by lat9 (lat9@vinosdefrutastropicales.com), v1.0.0.
// Copyright (C) 2017, Vinos de Frutas Tropicales
//
class NotifierCheck
{
    // -----
    // When the class is initially constructed, save the text associated with a sprintf-formatted string
    // that is used if one or more notifiers is missing from a given file.
    //
    // That message constant **MUST** include two replacements:
    //
    // %$1s ... Will be set to the file name that has missing notifiers.
    // %$2s ... Will be set to contain the comma-separated list of missing notifiers.
    //
    public function __construct ($format_notifier_missing, $format_file_missing = '')
    {
        $this->format_notifier_missing = $format_notifier_missing;
        $this->format_file_missing = $format_file_missing;
        $this->check_list = array ();
    }
    
    // -----
    // This function identifies the files and associated notifiers that must be present, taking as
    // input an associative array in the following format:
    //
    // $list_array = array (
    //     array (
    //         'filename' => $the_files_name_including_path,
    //         'required' => {true|false},  if set to true, the file must be present or a message is issued.
    //         'notifiers' => array (
    //            'NOTIFIER_1',
    //            ...
    //         ),
    //     ),
    //     ...
    // );
    //
    public function setList ($list_array) {
        if (!is_array ($list_array)) {
            trigger_error ("Interface error, input is not an array:" . var_export ($list_array, true), E_USER_ERROR);
        } else {
            $this->check_list = $list_array;
        }
    }

    public function process ()
    {
        global $messageStack;
        $all_files_ok = true;
        foreach ($this->check_list as $current_check) {
            if (!isset ($current_check['filename']) || !isset ($current_check['required']) || !isset ($current_check['notifiers']) || !is_array ($current_check['notifiers'])) {
                $all_files_ok = false;
                trigger_error ("Interface error, missing check-list elements:\n" . var_export ($this->check_list, true), E_USER_ERROR);
            } else {
                $filename = $current_check['filename'];
                if (!file_exists ($filename)) {
                    if ($current_check['required']) {
                        $all_files_ok = false;
                        $messageStack->add_session (sprintf ($this->format_file_missing, $filename), 'error');
                    }
                } else {
                    $file_check = file_get_contents ($filename);
                    if ($file_check !== false) {
                        $not_found_list = array ();
                        foreach ($current_check['notifiers'] as $current_notifier) {
                            if (strpos ($file_check, "'$current_notifier'") === false) {
                                $not_found_list[] = $current_notifier;
                            }
                        }
                        if (count ($not_found_list) != 0) {
                            $not_found_list = implode (', ', $not_found_list);
                            $messageStack->add_session (sprintf ($this->format_notifier_missing, $filename, $not_found_list), 'error');
                        }
                    }
                }
            }
        }
        return $all_files_ok;
    }
}
