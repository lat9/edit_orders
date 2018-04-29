<?php
// -----
// This auto-loader loads the Edit Orders additional module that checks to see if conditions
// exist in an order that might lead to "mis-handling".
//
if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }

$autoLoadConfig[200][] = array(
	'autoType' => 'init_script',
    'loadFile' => 'edit_orders_cautions.php'
);