<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
// 
if (!defined ('IS_ADMIN_FLAG')) { 
    die ('Illegal Access'); 
}

$autoLoadConfig[0][] = array (
    'autoType' => 'class',
    'loadFile' => 'mock_cart.php',
    'classPath' => DIR_FS_ADMIN . DIR_WS_CLASSES
);
