<?php
// -----
// Admin-level auto-loader for the Edit Orders plugin for Zen Cart, provided by lat9 and others.
// 
if (!defined ('IS_ADMIN_FLAG')) { 
    die ('Illegal Access'); 
}

$autoLoadConfig[0][] = array(
    'autoType' => 'class',
    'loadFile' => 'mock_cart.php',
    'classPath' => DIR_FS_ADMIN . DIR_WS_CLASSES
);

$autoLoadConfig[200][] = array(
    'autoType' => 'init_script',
    'loadFile' => 'init_eo_config.php'
);

// -----
// Instantiate EO's admin observer-class (hopefully!) as the last observer to be loaded.  This
// allows other observers of the ot_shipping's NOTIFY_OT_SHIPPING_TAX_CALCS notification to do
// their thing first.
//
$autoLoadConfig[999][] = array(
    'autoType'  => 'class',
    'loadFile'  => 'observers/EditOrdersAdminObserver.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[999][] = array(
    'autoType'   => 'classInstantiate',
    'className'  => 'EditOrdersAdminObserver',
    'objectName' => 'EditOrdersAdminObserver'
);
