<?php
// -----
// Admin-level initialization script for the Edit Orders plugin for Zen Cart, by lat9.
// Copyright (C) 2018, Vinos de Frutas Tropicales.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('EO_CURRENT_VERSION', '4.3.4');

// -----
// Only update configuration when an admin is logged in.
//
if (isset($_SESSION['admin_id'])) {
    $configurationGroupTitle = 'Edit Orders';
    $configuration = $db->Execute(
        "SELECT configuration_group_id 
           FROM " . TABLE_CONFIGURATION_GROUP . " 
          WHERE configuration_group_title = '$configurationGroupTitle' 
          LIMIT 1"
    );
    if ($configuration->EOF) {
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
                (configuration_group_title, configuration_group_description, sort_order, visible) 
            VALUES 
                ('$configurationGroupTitle', '$configurationGroupTitle', '1', '1')"
        );
        $cgi = $db->Insert_ID(); 
        $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi;");
    } else {
        $cgi = $configuration->fields['configuration_group_id'];
    }

    // ----
    // If not already set, record the configuration's current version in the database.
    //
    if (!defined('EO_VERSION')) {
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function) 
             VALUES 
                ('Edit Orders Version', 'EO_VERSION', '" . EO_CURRENT_VERSION . "', 'The currently-installed version of the plugin.', $cgi, 1, now(), 'trim(')"
        );

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
             VALUES 
                ( 'Reset Totals on Update &mdash; Default', 'EO_TOTAL_RESET_DEFAULT', 'off', 'Choose the default value for the <em>Reset totals prior to update</em> checkbox.  If your store uses order-total modules that perform tax-related recalculations (like &quot;Group Pricing&quot;), set this value to <b>on</b>.', $cgi, 5, now(), NULL, 'zen_cfg_select_option(array(\'on\', \'off\'),')"
        );
        
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
             VALUES 
                ( 'Use a mock shopping cart?', 'EO_MOCK_SHOPPING_CART', 'true', 'When enabled, a mock shopping cart is created which reads product information from the current order. Many of the 3rd party \"Discount\" Order Total modules were not designed to be run from the Zen Cart administrative interface and require this option to be enabled.<br /><br />The mock shopping cart only provides the get_products and in_cart methods.<br /><br />If installed order total or shipping modules require additional methods from the shopping cart, the mock cart should be disabled.', $cgi, 10, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
      
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
             VALUES 
                ( 'Strip tags from the shipping module name?', 'EO_SHIPPING_DROPDOWN_STRIP_TAGS', 'true', 'When enabled, HTML and PHP tags present in the title of a shipping module are removed from the text displayed in the shipping dropdown menu.<br /><br />If partial or broken tags are present in the title it may result in the removal of more text than expected. If this happens, you will need to update the affected shipping module(s) or disable this option.', $cgi, 11, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
      
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
             VALUES 
                ( 'Debug Action Level', 'EO_DEBUG_ACTION_LEVEL', '0', 'When enabled when actions are performed by Edit Orders additional debugging information will be stored in a log file.<br /><br />Enabling debugging will result in a large number of created log files and may adversely affect server performance. Only enable this if absolutely necessary!', $cgi, 12, now(), NULL, 'eo_debug_action_level_list(')"
        );
      
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
             VALUES 
                ( 'Edit Orders File Missing', 'EO_INIT_FILE_MISSING', '0', 'This (hidden) value is set to 1 if <em>EO</em> has detected missing files.', 6, 92, now(), NULL, NULL)"
        );
        
        // -----
        // Register the admin pages for the plugin.
        //
        $next_sort = $db->Execute("SELECT MAX(sort_order) as max_sort FROM " . TABLE_ADMIN_PAGES . " WHERE menu_key='customers'", false, false, 0, true);
        zen_register_admin_page('editOrders', 'BOX_CONFIGURATION_EDIT_ORDERS', 'FILENAME_EDIT_ORDERS', '', 'customers', 'N', $next_sort->fields['max_sort'] + 1);
        zen_register_admin_page('configEditOrders', 'BOX_CONFIGURATION_EDIT_ORDERS', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y', $cgi);
        
        define('EO_INIT_FILE_MISSING', '1');  //-Set so that the notifier checks will run upon initial installation.
        define('EO_VERSION', '0.0.0');
        
        $messageStack->add(sprintf(EO_INIT_INSTALLED, EO_CURRENT_VERSION), 'success');
        
    // -----
    // Otherwise, we're updating an existing version; perform any configuration changes necessary.
    //
    } else {
        if (EO_VERSION <= '4.1.1') {
            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'EO_SHIPPING_TAX'
                  LIMIT 1"
            );
        }
        
        if (version_compare(EO_VERSION, '4.3.0', '<')) {
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                    ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
                 VALUES 
                    ( 'Reset Totals on Update &mdash; Default', 'EO_TOTAL_RESET_DEFAULT', 'off', 'Choose the default value for the <em>Reset totals prior to update</em> checkbox.  If your store uses order-total modules that perform tax-related recalculations (like &quot;Group Pricing&quot;), set this value to <b>on</b>.', $cgi, 5, now(), NULL, 'zen_cfg_select_option(array(\'on\', \'off\'),')"
            );
          
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                    ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
                 VALUES 
                    ( 'Edit Orders File Missing', 'EO_INIT_FILE_MISSING', '0', 'This (hidden) value is set to 1 if <em>EO</em> has detected missing files.', 6, 92, now(), NULL, NULL)"
            );
            
            if (!defined('EO_INIT_FILE_MISSING')) {
                define('EO_INIT_FILE_MISSING', '1');
            }
        }
        
        if (version_compare(EO_VERSION, '4.3.4', '<')) {
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                    ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
                 VALUES 
                    ( 'Product Price Calculation &mdash; Method', 'EO_PRODUCT_PRICE_CALC_METHOD', 'Auto', 'Choose the <em>method</em> that &quot;EO&quot; uses to calculate product prices when an order is updated, one of:<ol><li><b>Auto</b>: Each product-price is re-calculated.  If your products have attributes, this enables changes to a product\'s attributes to automatically update the associated product-price.</li><li><b>Manual</b>: Each product-price is based on the <b><i>admin-entered price</i></b> for the product.</li><li><b>Choose</b>: The product-price calculation method varies on an order-by-order basis, via the &quot;tick&quot; of a checkbox.  The default method used (<em>Auto</em> vs. <em>Manual</em> is defined by the <em>Product Price Calculation &mdash; Default</em> setting.</li></ol>', $cgi, 20, now(), NULL, 'zen_cfg_select_option(array(\'Auto\', \'Manual\', \'Choose\'),')"
            );
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                    ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
                 VALUES 
                    ( 'Product Price Calculation &mdash; Default', 'EO_PRODUCT_PRICE_CALC_DEFAULT', 'Auto', 'If the product price-calculation method is <b>Choose</b>, what method should be used as the <em>default</em> method?', $cgi, 24, now(), NULL, 'zen_cfg_select_option(array(\'Auto\', \'Manual\'),')"
            );
        }
    }

    // -----
    // Update the configuration reflect the current EO version.
    //
    if (EO_VERSION != EO_CURRENT_VERSION && EO_VERSION != '0.0.0') {
        $messageStack->add(sprintf(EO_INIT_VERSION_UPDATED, EO_VERSION, EO_CURRENT_VERSION), 'success');
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_value = '" . EO_CURRENT_VERSION . "'
              WHERE configuration_key = 'EO_VERSION'
              LIMIT 1"
        );
    }
    
    // -----
    // On initial installation, upgrade from a version prior to 4.3.0 or if the required notifiers'
    // check previously failed, check for the notifiers required by EO.
    //
    if (EO_INIT_FILE_MISSING == '1') {
        $notifier_check = new \Vinos\Common\NotifierCheck(EO_INIT_MISSING_NOTIFIERS, EO_INIT_MISSING_FILES);
        $notifier_check->setList(
            array(
                array(
                    'filename' => DIR_FS_ADMIN . 'orders.php',
                    'required' => true,
                    'notifiers' => array(
                        'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS', 
                        'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END',
                        'NOTIFY_ADMIN_ORDERS_EDIT_BUTTONS',
                        'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',
                    ),
                ),
            )
        );
        $notifiers_missing = ($notifier_check->process()) ? '0' : '1';
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_value = '$notifiers_missing'
              WHERE configuration_key = 'EO_INIT_FILE_MISSING'
              LIMIT 1"
        );
    }
}