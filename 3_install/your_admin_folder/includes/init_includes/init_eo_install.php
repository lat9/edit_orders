<?php

if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }

@require_once DIR_FS_ADMIN . DIR_WS_CLASSES . 'eo_plugin.php';
$plugin = new eo_plugin();
$plugin->install();
