<?php
// -----
// Admin-level auto-loader for the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Last updated: v5.0.0
// 
if (!defined ('IS_ADMIN_FLAG')) { 
    die ('Illegal Access'); 
}

// -----
// Load point 63 is after the session's initialization [60] but before the
// languages are loaded [65].
//
// That gives EO's configuration script a chance to identify the current page
// as 'edit_orders' for EO's AJAX processing, so that the associated language
// constants will be pulled in for EO during that AJAX processing.
//
$autoLoadConfig[63][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_eo_config.php'
];

// -----
// Instantiate EO's admin observer-class (hopefully!) as the last observer to be loaded.  This
// allows other observers of the ot_shipping's NOTIFY_OT_SHIPPING_TAX_CALCS notification to do
// their thing first.
//
$autoLoadConfig[999][] = [
    'autoType'  => 'class',
    'loadFile'  => 'observers/EditOrdersAdminObserver.php',
    'classPath' => DIR_WS_CLASSES
];
$autoLoadConfig[999][] = [
    'autoType'   => 'classInstantiate',
    'className'  => 'EditOrdersAdminObserver',
    'objectName' => 'EditOrdersAdminObserver'
];
