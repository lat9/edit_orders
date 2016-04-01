<?php

/**
 * Language file for plugin classes
 *
 * @copyright Copyright 2012 - 2014 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

// Install messages
define('PLUGIN_INSTALL_SUCCESS', '%1$s installation / upgrade completed!');
define('PLUGIN_INSTALL_ERROR', '%1$s installation / upgrade failed!');
define('PLUGIN_INSTALL_ERROR_FILE_FOUND', 'Filesystem Error: Unable to delete \'%2$s\'. Verify your web server has access to delete this file. Installation will not continue until the web server is granted access to remove the file.');
define('PLUGIN_INSTALL_ERROR_FILE_NOT_FOUND', 'Filesystem Error: Unable to access \'%2$s\'. Please make sure the file exists on the webserver and the webserver has access to read the file!');
define('PLUGIN_INSTALL_ERROR_DATABASE_TABLE', 'Database Error: Unable to create table \'%2$s\'!');
define('PLUGIN_INSTALL_ERROR_DATABASE_KEY', 'Database Error: Unable to create \'%3$s\' in table \'%2$s\'!');
define('PLUGIN_INSTALL_ERROR_ADMIN_PAGE', 'Error: Unable to register admin pages. This can be caused by a database access / configuration issue or damage to core Zen Cart files.');
define('PLUGIN_INSTALL_ERROR_AUTOLOAD', 'Filesystem Error: Unable to delete \'%2$s\'. For this module to work you must delete this file manually. <strong>Do not click around in the administrative interface until you have manually deleted the file</strong>.');
define('PLUGIN_INSTALL_ERROR_SORT_ORDER', 'Database Error: Unable to access sort_order in table \'%2$s\'!');
define('PLUGIN_INSTALL_FOUND_PREVIOUS_VERSION', 'Found previous version installed. Upgrading (or re-installing) %1$s (old %2$s =&gt; new %3$s).');
define('PLUGIN_INSTALL_ERROR_FILE_BACKUP', 'Filesystem Error: Unable to create a backup of \'%2$s\'.');
define('PLUGIN_INSTALL_ERROR_FILE_WRITE', 'Filesystem Error: Unable to write to \'%2$s\'. Please verify the webserver has access to write to file.<br />Installation will not continue until the web server is granted access to write and delete the file.');
define('PLUGIN_INSTALL_ERROR_FILE_CHECK', 'Filesystem Error: A file \'%2$s\' required to be modified for this plugin appears to not be modified. Please verify the modifications to the plugin including any comments.<br />Installation will not continue until the checks run successfully.');

// Removal messages
define('PLUGIN_REMOVE_SUCCESS', '%1$s removal completed! Do not forget to undo any modifications made by %1$s to core files');
define('PLUGIN_REMOVE_ERROR', '%1$s removal failed!');
define('PLUGIN_REMOVE_ERROR_FILE_FOUND', 'Filesystem Error: Unable to delete \'%2$s\'. Verify your web server has access to delete this file. Removal will not continue until the web server is granted access to remove the file.');
define('PLUGIN_REMOVE_ERROR_DATABASE_TABLE', 'Database Error: Unable to delete table \'%2$s\'!');
define('PLUGIN_REMOVE_ERROR_DATABASE_KEY', 'Database Error: Unable to delete \'%3$s\' in table \'%2$s\'!');
define('PLUGIN_REMOVE_ERROR_ADMIN_PAGE', 'Error: Unable to remove the admin pages. This can be caused by a database access / configuration issue or damage to core Zen Cart files.');
define('PLUGIN_REMOVE_ERROR_AUTOLOAD', 'Filesystem Error: Unable to delete \'%2$s\'. For this module to work you must delete this file manually. <strong>Do not click around in the administrative interface until you have manually deleted the file</strong>.');
define('PLUGIN_REMOVE_ERROR_FILE_WRITE', 'Filesystem Error: Unable to write to \'%2$s\'. Please verify the webserver has access to write to file.<br />Removal will not continue until the web server is granted access to write and delete the file.');

// Store Configuration warnings
define('PLUGIN_CONFIG_ADMIN_WARNINGS', 'Some potential issues were found in your &quot;admin&quot; configure.php. <strong>Failure to correct these issues may result in Zen Cart not functioning as intended.</strong><br />The &quot;admin&quot; configure.php file can be found at <code>%1$s</code>.');
define('PLUGIN_CONFIG_STORE_WARNINGS', 'Some potential issues were found in your &quot;store / catalog&quot; configure.php. <strong>Failure to correct these issues may result in Zen Cart not functioning as intended.</strong><br />The &quot;store / catalog&quot; configure.php file can be found at <code>%1$s</code>.');
define('PLUGIN_CONFIG_REMOVE_TRAILING_SUBDIRECTORY', 'The path to the subdirectory must be removed from <code>%1$s</code>.');
define('PLUGIN_CONFIG_REMOVE_TRAILING_SLASH', 'The <code>%1$s</code> setting cannot end with a slash. Remove the extra <code>/</code> from the end of <code>%1$s</code>');
define('PLUGIN_CONFIG_SUBDIRECTORY_INFO', 'If the store is in a subdirectory<ul><li>The name of the subdirectory should be placed in <code>%1$s</code>.</li><li>The subdirectory should be preceded and followed by slashes, e.g. <code>/store/</code>.</li><li>The subdirectory name <strong>cannot</strong> be included in the <code>%2$s</code> setting.</li></ul>');
define('PLUGIN_CONFIG_SUBDIRECTORY_EMPTY', 'The value for <code>%1$s</code> cannot be blank. If the store installed at the website root (not in a subdirectory), set the value for <code>%1$s</code> to <code>/</code>. If the store is in a subdirectory, the name of the subdirectory should be placed in <code>%1$s</code>. The subdirectory should be preceded and followed by slashes, e.g. <code>/store/</code>.');
define('PLUGIN_CONFIG_ADD_LEADING_SLASH', 'The <code>%1$s</code> setting must start with a slash. Add a <code>/</code> to the start of <code>%1$s</code>.');
define('PLUGIN_CONFIG_ADD_TRAILING_SLASH', 'The <code>%1$s</code> setting must end with a slash. Add a <code>/</code> to the end of <code>%1$s</code>.');
define('PLUGIN_CONFIG_STORE_CONFIGURE', 'Unable to load the store\'s configure.php file <code>%1$s</code>. Verify the file exists at this location and the webserver has permissions to read the file. The path is determined by reading: <code>%2$s</code>.');

// Internal Messages
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_KEY', 'Invalid Admin Page Options specified. Please contact the maintainer of \'%1$s\'. Pages\'s must specify the language_key AND specify either the page_key or have the page_key as the array key.');
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY', 'Invalid Admin Page Options specified. Please contact the maintainer of \'%1$s\'. Pages\'s must specify the menu_key (unless the main_page is \'FILENAME_CONFIGURATION\' or \'FILENAME_MODULES\').');
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY_BROKEN', 'The Admin Menu \'%2$s\' does not appear to exist. This menu is part of the default Zen Cart installation. Failure to locate this menu may indicate your Zen Cart database is damaged or someone has manually removed one of the core Zen Cart menus.');
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY_MISSING', 'The Admin Menu \'%2$s\' does not appear to exist. This menu is not part of the default Zen Cart installation. Please contact the maintainer of \'%1$s\'.');

// Common configuration options
define('PLUGIN_OPTION_VERSION_TITLE', '%1$s Version');
define('PLUGIN_OPTION_VERSION_DESCRIPTION', 'Indicates the currently installed version of %1$s.');
define('PLUGIN_CONFIG_GROUP_DESCRIPTION', 'Configuration Group for %1$s');