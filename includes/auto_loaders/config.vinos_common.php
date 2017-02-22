<?php
// -----
// Supports loading the "Vinos" namespace for classes provided by lat9 (@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.
//
// v1.0.0 ... 2017-02-22
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
// -----
// Instantiate and initialize the auto-loader.
//
$autoLoadConfig[0][] = array (
    'autoType' => 'init_script',
    'loadFile' => 'init_vinos_autoload.php'
);
