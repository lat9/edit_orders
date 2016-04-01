<?php

@require_once DIR_FS_ADMIN . DIR_WS_CLASSES . 'plugin.php';
class eo_plugin extends plugin {
	public function getVersion() { return '4.1.4'; }
	public function getUniqueKey() { return 'EO'; }
	public function getUniqueName() { return BOX_CONFIGURATION_EDIT_ORDERS; }
	public function getDescription() { return EO_DESCRIPTION; }
	public function getDefaultConfiguration() {
		return array(
			'MOCK_SHOPPING_CART' => array('configuration_value' => 'true', 'use_function' => '', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
			'SHIPPING_DROPDOWN_STRIP_TAGS' => array('configuration_value' => 'true', 'use_function' => '', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
			'DEBUG_ACTION_LEVEL' => array('configuration_value' => '0', 'use_function' => '', 'set_function' => 'eo_debug_action_level_list('),
		);
	}
	public function getAdminPages() {
		return array(
			'editOrders' => array(
				'language_key' => 'BOX_CONFIGURATION_EDIT_ORDERS',
				'main_page' => 'FILENAME_EDIT_ORDERS',
				'menu_key' => 'customers'
			),
			'configEditOrders' => array(
				'language_key' => 'BOX_CONFIGURATION_EDIT_ORDERS'
			)
		);
	}
	public function getNewFiles() {
		return array(
			DIR_FS_ADMIN . 'edit_orders.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'edit_orders.css',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'auto_loaders/config.eo.php',
			DIR_FS_ADMIN . DIR_WS_CLASSES . 'attributes.php',
			DIR_FS_ADMIN . DIR_WS_CLASSES . 'mock_cart.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_configures/edit_orders.php',
			DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'extra_functions/edit_orders_functions.php',
			DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/edit_orders.php',
			DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/extra_definitions/edit_orders.php',
			DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/images/buttons/button_add_product.gif',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_billing.png',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_comment_add.png',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_customers.png',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_shipping.png',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_track_add.png',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_details.gif',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_invoice.gif'
		);
	}
	public function getObsoleteFiles() {
		return array(
			DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'extra_functions/common_orders_functions.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'init_includes/init_eo_config.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/edit_orders_defines.php'
		);
	}
	public function getModifiedCoreFiles($install = true) {
		return array(
			// TODO: Add either search / replace keys or checks
			DIR_FS_CATALOG . DIR_WS_CLASSES . 'order_total.php',
			DIR_FS_CATALOG . DIR_WS_CLASSES . 'shipping.php',

			DIR_FS_ADMIN . 'orders.php',
			DIR_FS_ADMIN . DIR_WS_IMAGES . 'icon_edit.gif',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'init_includes/init_currencies.php',
			DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/orders.php'
		);
	}
	public function handleFileChanges($install = true) {
		$success = parent::handleFileChanges($install);

		$changes = array();
		if($install) {
			// Handle updating files for versions of Super Orders <= 4.0.5
			if(defined('SO_VERSION') && version_compare(SO_VERSION, '4.0.5', '<=')) {
				$changes[DIR_FS_ADMIN . FILENAME_ORDERS . '.php'] = array(
					'search' => '/FILENAME_ORDER_EDIT/',
					'replace' => 'FILENAME_EDIT_ORDERS'
				);
			}

			// Handle updating files for older versions of Admin New Order
			if(defined('FILENAME_NEW_ORDER') && !defined('ANO_VERSION')) {
				$changes[DIR_FS_ADMIN . FILENAME_ORDERS . '.php'] = array(
					'search' => '/FILENAME_ORDER_EDIT/',
					'replace' => 'FILENAME_EDIT_ORDERS'
				);
				$changes[DIR_FS_ADMIN . FILENAME_NEW_ORDER . '.php'] = array(
					'search' => '/FILENAME_ORDER_EDIT/',
					'replace' => 'FILENAME_EDIT_ORDERS'
				);
			}
		}

		return  $this->processFileChanges($changes) && $success;
	}

	public function handleUpgradeFrom($version) {
		$success = true;
		switch($version) {
			case '4.0.2': // 4.0.3 and 4.0.4 left this set to 4.0.2
			case '4.1':
			case '4.1.1':
				// Remove the obsolete "Shipping Tax" configuration option
				$this->removeConfigurationOption('SHIPPING_TAX');

				// We changed a definition related to the edit orders admin page
				if(function_exists('zen_deregister_admin_pages') && zen_page_key_exists('editOrders')) zen_deregister_admin_pages('editOrders');
				if(function_exists('zen_deregister_admin_pages') && zen_page_key_exists('configEditOrders')) zen_deregister_admin_pages('configEditOrders');

				break;
			default:
				break;
		}
		return $success;
	}

	// Override some paths inside the created Zip File
	protected function getZipArchivePath($type = null) {
		$paths = array(
			'readme' => 'readme',
			'optional' => '4_optional_items',
			'remove' => '5_uninstall'
		);

		if($type !== null) {
			if(array_key_exists($type, $paths)) return $paths[$type];
			return parent::getZipArchivePath($type);
		}

		return array_merge(parent::getZipArchivePath(), $paths);
	}

	protected function addAdditionalFilesToZipArchive($zip) {
		// Add the license file if present
		$license = realpath(DIR_FS_CATALOG . '../LICENSE');
		if(file_exists($license)) $zip->addFile($license, 'LICENSE');

		// Add the readme files if present
		$readme = realpath(DIR_FS_CATALOG . '../readme');
		if(is_dir($readme)) {
			if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
				$files = new RecursiveDirectoryIterator($readme, FilesystemIterator::CURRENT_AS_SELF);
			}
			else {
				$files = new RecursiveDirectoryIterator($readme);
			}
			$files = new RecursiveIteratorIterator($files);
			foreach($files as $file) {
				if($file->isDot()) continue;
				$local = $this->getZipArchivePath('readme') . substr($file->getPathname(), strlen($readme));
				$zip->addFile($file->getPathname(), $local);
			}
		}

		// Add the supporting files for optional components
		$optional = realpath(DIR_FS_CATALOG . '../optional');
		if(is_dir($optional)) {
			if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
				$files = new RecursiveDirectoryIterator($optional, FilesystemIterator::CURRENT_AS_SELF);
			}
			else {
				$files = new RecursiveDirectoryIterator($optional);
			}
			$files = new RecursiveIteratorIterator($files);
			foreach($files as $file) {
				if($file->isDot()) continue;
				$local = $this->getZipArchivePath('optional') . substr($file->getPathname(), strlen($optional));
				$zip->addFile($file->getPathname(), $local);
			}
		}

		// Add the "Onetime Discount" files
		return $this->addToZipArchive($zip, $this->getZipArchivePath('optional') . '/1_onetime_discount/1_new_files', $this->getOnetimeDiscountFiles());
	}

	private function getOnetimeDiscountFiles() {
		return array(
			DIR_FS_CATALOG . DIR_WS_LANGUAGES . 'english/modules/order_total/ot_onetime_discount.php',
			DIR_FS_CATALOG . DIR_WS_MODULES . 'order_total/ot_onetime_discount.php'
		);
	}
}