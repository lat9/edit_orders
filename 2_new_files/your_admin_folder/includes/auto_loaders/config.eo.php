<?php
if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }

$autoLoadConfig[0][] = array(
	'autoType'=>'class',
	'loadFile'=>'mock_cart.php',
	'classPath'=> DIR_FS_ADMIN . DIR_WS_CLASSES
);