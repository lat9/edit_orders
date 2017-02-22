<?php
// -----
// Supports loading the "Vinos" namespace for classes provided by lat9 (@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.
//
// v1.0.0 ... 2017-02-22
//
require (DIR_FS_CATALOG . DIR_WS_CLASSES . 'VinosAutoload.php');

$vinos_loader = new \Vinos\VinosAutoload;

$vinos_loader->register ();
$vinos_loader->addNamespace ('Vinos\Common', DIR_FS_CATALOG . DIR_WS_CLASSES . 'vinos/common');
$vinos_loader->addNamespace ('Vinos', DIR_FS_CATALOG . DIR_WS_CLASSES . 'vinos');
