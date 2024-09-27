<?php
// -----
// Admin-level installation script for the "encapsulated" Edit Orders plugin for Zen Cart, by lat9.
// Copyright (C) 2018-2024, Vinos de Frutas Tropicales.
//
// Last updated: v5.0.0 (new)
//
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    private string $configGroupTitle = 'Edit Orders';

    protected function executeInstall()
    {
        if ($this->nonEncapsulatedVersionPresent() === true) {
            $this->errorContainer->addError('error', ZC_PLUGIN_EO_INSTALL_REMOVE_PREVIOUS, true);
            return false;
        }

        // -----
        // First, determine the configuration-group-id and install the settings.
        //
        $cgi = $this->getOrCreateConfigGroupId(
            $this->configGroupTitle,
            $this->configGroupTitle . ' Settings'
        );

        $sql =
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
             VALUES
                ('Addresses, Display Order', 'EO_ADDRESSES_DISPLAY_ORDER', 'CSB', 'In what order, left-to-right, should <em>Edit Orders</em> display an order\'s addresses?  Choose <b>CSB</b> to display <em>Customer</em>, <em>Shipping</em> and then <em>Billing</em>; choose <b>CBS</b> to display <em>Customer</em>, <em>Billing</em> and then <em>Shipping</em>.', $cgi, 1, now(), NULL, 'zen_cfg_select_option([\'CSB\', \'CBS\'],'),

                ('Reset Totals on Update &mdash; Default', 'EO_TOTAL_RESET_DEFAULT', 'off', 'Choose the default value for the <em>Reset totals prior to update</em> checkbox.  If your store uses order-total modules that perform tax-related recalculations (like &quot;Group Pricing&quot;), set this value to <b>on</b>.', $cgi, 5, now(), NULL, 'zen_cfg_select_option([\'on\', \'off\'],'),

                ('Strip tags from the shipping module name?', 'EO_SHIPPING_DROPDOWN_STRIP_TAGS', 'true', 'When enabled, HTML and PHP tags present in the title of a shipping module are removed from the text displayed in the shipping dropdown menu.<br><br>If partial or broken tags are present in the title it may result in the removal of more text than expected. If this happens, you will need to update the affected shipping module(s) or disable this option.', $cgi, 11, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

                ('Product Price Calculation &mdash; Method', 'EO_PRODUCT_PRICE_CALC_METHOD', 'AutoSpecials', 'Choose the <em>method</em> that &quot;EO&quot; uses to calculate product prices when an order is updated, one of:<ol><li><b>AutoSpecials</b>: Each product-price is re-calculated as if placing the order on the storefront. If your products have attributes, this enables changes to a product\'s attributes to automatically update the associated product-price.</li><li><b>Manual</b>: Each product-price is based on the <b><i>admin-entered price</i></b> for the product.</li><li><b>Choose</b>: The product-price calculation method varies on an order-by-order basis, via the &quot;tick&quot; of a checkbox.  The default method used  is defined by the <em>Product Price Calculation &mdash; Default</em> setting.</li></ol>', $cgi, 20, now(), NULL, 'zen_cfg_select_option([\'AutoSpecials\', \'Manual\', \'Choose\'],'),

                ('Product Price Calculation &mdash; Default', 'EO_PRODUCT_PRICE_CALC_DEFAULT', 'AutoSpecials', 'If the product price-calculation method is <b>Choose</b>, what method should be used as the <em>default</em> method?', $cgi, 24, now(), NULL, 'zen_cfg_select_option([\'AutoSpecials\', \'Manual\'],'),

                ('Status-history Display Order', 'EO_STATUS_HISTORY_DISPLAY_ORDER', 'Asc', 'Choose the way that <em>Edit Orders</em> displays an order\'s status-history records, either as-recorded (<b>Asc</b>) or most-recent first (<b>Desc</b>).', $cgi, 30, now(), NULL, 'zen_cfg_select_option([\'Asc\', \'Desc\'],'),

                ('Status-update: Customer Notification Default', 'EO_CUSTOMER_NOTIFICATION_DEFAULT', 'Email', 'Choose the default used for the radio-buttons that identify whether the customer receives notification when a  comment is added to the order.', $cgi, 40, now(), NULL, 'zen_cfg_select_option([\'Email\', \'No Email\', \'Hidden\'],'),

                ('Show Edit-Order Icon on Orders\' Listing?', 'EO_SHOW_EDIT_ORDER_ICON', 'Yes', 'Should the edit-icon be shown for each order on the orders\' listing?  Default: <b>Yes</b>', $cgi, 50, now(), NULL, 'zen_cfg_select_option([\'Yes\', \'No\'],'),

                ('Edit Button Location on Sidebox', 'EO_SHOW_EDIT_ORDER_BUTTON', 'Both', 'At which position(s) should the <em>Edit</em> button be displayed on the currently-selected order\'s sidebox display, relative to the order\'s information?  Default: <b>Both</b>', $cgi, 52, now(), NULL, 'zen_cfg_select_option([\'Both\', \'Top Only\', \'Bottom Only\', \'Neither\'],'),

                ('Debug Action Level', 'EO_DEBUG_ACTION_LEVEL', '0', 'When enabled when actions are performed by Edit Orders additional debugging information will be stored in a log file.<br><br>Enabling debugging will result in a large number of created log files and may adversely affect server performance. Only enable this if absolutely necessary!', $cgi, 999, now(), NULL, 'eo_debug_action_level_list(')";
        $this->executeInstallerSql($sql);

        // -----
        // Record the plugin's base tools in the admin menus.
        //
        zen_deregister_admin_pages([
            'editOrders',
            'configEditOrders',
        ]);
        if (!zen_page_key_exists('editOrders')) {
            zen_register_admin_page('editOrders', 'BOX_CONFIGURATION_EDIT_ORDERS', 'FILENAME_EDIT_ORDERS', '', 'customers', 'N');
        }
        if (!zen_page_key_exists('configEditOrders')) {
            zen_register_admin_page('configEditOrders', 'BOX_CONFIGURATION_EDIT_ORDERS', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
        }

        // -----
        // If a previous (non-encapsulated) version of the plugin is currently installed,
        // perform any version-specific updates needed.
        //
        if (defined('EO_VERSION')) {
            $this->updateFromNonEncapsulatedVersion();
        }

        return true;
    }

    // -----
    // Not used, initially, but included for the possibility of future upgrades!
    //
    // Note: This (https://github.com/zencart/zencart/pull/6498) Zen Cart PR must
    // be present in the base code or a PHP Fatal error is generated due to the
    // function signature difference.
    //
    protected function executeUpgrade($oldVersion)
    {
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages([
            'editOrders',
            'configEditOrders',
        ]);

        $this->deleteConfigurationGroup($this->configGroupTitle, true);
    }

    protected function nonEncapsulatedVersionPresent(): bool
    {
        $log_messages = [];
        if (defined('FILENAME_EDIT_ORDERS')) {
            $log_messages[] = "'FILENAME_EDIT_ORDERS' definition is present";
        }

        $file_found_message = 'Non-encapsulated admin file (%s) must be removed before this plugin can be installed.';
        if (file_exists(DIR_FS_ADMIN . 'edit_orders.php')) {
            $log_messages[] = sprintf($file_found_message, 'edit_orders.php');
        }

        $files_to_check = [
            'includes/auto_loaders/' => [
                'config.eo.php',
                'config.eo_cautions.php',
            ],
            'includes/classes/' => [
                'attributes.php',
                'editOrders.php',
                'EditOrdersOtShippingStub.php',
                'EditOrdersQueryCache.php',
                'mock_cart.php',
                'observers/EditOrdersAdminObserver.php',
            ],
            'includes/extra_datafiles/' => [
                'edit_orders_defines.php',
                'eo_sanitization.php',
            ],
            'includes/functions/extra_functions/' => [
                'edit_orders_functions.php',
            ],
            'includes/init_includes/' => [
                'edit_orders_cautions.php',
                'init_eo_config.php',
            ],
            'includes/modules/edit_orders/' => [
                'eo_add_prdct_action_display.php',
                'eo_add_prdct_action_processing.php',
                'eo_common_address_format.php',
                'eo_edit_action_addresses_display.php',
                'eo_edit_action_display.php',
                'eo_edit_action_osh_table_display.php',
                'eo_edit_action_ot_table_display.php',
                'eo_update_order_action_processing.php',
                'eo_navigation.php',
            ],
        ];
        foreach ($files_to_check as $dir => $files) {
            $current_dir = DIR_FS_ADMIN . $dir;
            foreach ($files as $next_file) {
                if (file_exists($current_dir . $next_file)) {
                    $log_messages[] = sprintf($file_found_message, $dir . $next_file);
                }
            }
        }

        if (count($log_messages) !== 0) {
            trigger_error(implode("\n", $log_messages), E_USER_NOTICE);
            return true;
        }
        return false;
    }

    // -----
    // Ensure that the sort-order of EO's configuration settings are as provided on
    // the initial install and remove any no-longer-used settings.
    //
    protected function updateFromNonEncapsulatedVersion(): void
    {
        $key_to_sort = [
            'EO_ADDRESSES_DISPLAY_ORDER' => 1,
            'EO_TOTAL_RESET_DEFAULT' => 5,
            'EO_SHIPPING_DROPDOWN_STRIP_TAGS' => 11,
            'EO_PRODUCT_PRICE_CALC_METHOD' => 20,
            'EO_PRODUCT_PRICE_CALC_DEFAULT' => 24,
            'EO_STATUS_HISTORY_DISPLAY_ORDER' => 30,
            'EO_CUSTOMER_NOTIFICATION_DEFAULT' => 40,
            'EO_SHOW_EDIT_ORDER_ICON' => 50,
            'EO_SHOW_EDIT_ORDER_BUTTON' => 52,
            'EO_DEBUG_ACTION_LEVEL' => 999,
        ];
        foreach ($key_to_sort as $key => $sort) {
            $this->updateConfigurationKey($key, ['sort_order' => $sort]);
        }

        $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN (
                'EO_VERSION',
                'EO_SHIPPING_TAX',
                'EO_MOCK_SHOPPING_CART',
                'EO_INIT_FILE_MISSING'
              )"
        );

        // -----
        // EO 5.0.0 removes support for 'Auto' pricing updates.
        //
        $this->updateConfigurationKey('EO_PRODUCT_PRICE_CALC_METHOD', [
            'configuration_description' =>
                'Choose the <em>method</em> that &quot;EO&quot; uses to calculate product prices when an order is updated, one of:<ol><li><b>AutoSpecials</b>: Each product-price is re-calculated as if placing the order on the storefront. If your products have attributes, this enables changes to a product\'s attributes to automatically update the associated product-price.</li><li><b>Manual</b>: Each product-price is based on the <b><i>admin-entered price</i></b> for the product.</li><li><b>Choose</b>: The product-price calculation method varies on an order-by-order basis, via the &quot;tick&quot; of a checkbox.  The default method used is defined by the <em>Product Price Calculation &mdash; Default</em> setting.</li></ol>',
            'set_function' => 'zen_cfg_select_option([\'AutoSpecials\', \'Manual\', \'Choose\'],'
        ]);
        if (defined('EO_PRODUCT_PRICE_CALC_METHOD') && EO_PRODUCT_PRICE_CALC_METHOD === 'Auto') {
            $this->updateConfigurationKey('EO_PRODUCT_PRICE_CALC_METHOD', [
                'configuration_value' => 'AutoSpecials',
            ]);
        }

        $this->updateConfigurationKey('EO_PRODUCT_PRICE_CALC_DEFAULT', [
            'set_function' => 'zen_cfg_select_option([\'AutoSpecials\', \'Manual\'],',
        ]);
        if (defined('EO_PRODUCT_PRICE_CALC_DEFAULT') && EO_PRODUCT_PRICE_CALC_DEFAULT === 'Auto') {
            $this->updateConfigurationKey('EO_PRODUCT_PRICE_CALC_DEFAULT', [
                'configuration_value' => 'AutoSpecials',
            ]);
        }
    }
}
