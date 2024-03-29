<?php
// -----
// Admin-level initialization script for the Edit Orders plugin for Zen Cart, by lat9.
// Copyright (C) 2018-2024, Vinos de Frutas Tropicales.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('EO_CURRENT_VERSION', '4.7.1');

// -----
// Only update configuration when an admin is logged in.
//
if (!isset($_SESSION['admin_id'])) {
    return;
}

// -----
// Determine EO configuration group only if not installed or not current
//
if (!defined('EO_VERSION') || EO_VERSION !== EO_CURRENT_VERSION) {
    // -----
    // Continue checking, since there's a logged-in admin to view any messages.
    //
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
                ('$configurationGroupTitle', '$configurationGroupTitle', 1, 1)"
        );
        $cgi = $db->Insert_ID(); 
        $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi LIMIT 1");
    } else {
        $cgi = $configuration->fields['configuration_group_id'];
    }
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
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES 
            ('Reset Totals on Update &mdash; Default', 'EO_TOTAL_RESET_DEFAULT', 'off', 'Choose the default value for the <em>Reset totals prior to update</em> checkbox.  If your store uses order-total modules that perform tax-related recalculations (like &quot;Group Pricing&quot;), set this value to <b>on</b>.', $cgi, 5, now(), NULL, 'zen_cfg_select_option([\'on\', \'off\'],')"
    );

    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Use a mock shopping cart?', 'EO_MOCK_SHOPPING_CART', 'true', 'When enabled, a mock shopping cart is created which reads product information from the current order. Many of the 3rd party \"Discount\" Order Total modules were not designed to be run from the Zen Cart administrative interface and require this option to be enabled.<br><br>The mock shopping cart only provides the get_products and in_cart methods.<br><br>If installed order total or shipping modules require additional methods from the shopping cart, the mock cart should be disabled.', $cgi, 10, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],')"
    );

    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Strip tags from the shipping module name?', 'EO_SHIPPING_DROPDOWN_STRIP_TAGS', 'true', 'When enabled, HTML and PHP tags present in the title of a shipping module are removed from the text displayed in the shipping dropdown menu.<br><br>If partial or broken tags are present in the title it may result in the removal of more text than expected. If this happens, you will need to update the affected shipping module(s) or disable this option.', $cgi, 11, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],')"
    );

    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Debug Action Level', 'EO_DEBUG_ACTION_LEVEL', '0', 'When enabled when actions are performed by Edit Orders additional debugging information will be stored in a log file.<br><br>Enabling debugging will result in a large number of created log files and may adversely affect server performance. Only enable this if absolutely necessary!', $cgi, 12, now(), NULL, 'eo_debug_action_level_list(')"
    );

    // -----
    // Register the admin pages for the plugin.
    //
    zen_register_admin_page('editOrders', 'BOX_CONFIGURATION_EDIT_ORDERS', 'FILENAME_EDIT_ORDERS', '', 'customers', 'N');
    zen_register_admin_page('configEditOrders', 'BOX_CONFIGURATION_EDIT_ORDERS', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');

    define('EO_VERSION', '0.0.0');

    $messageStack->add(sprintf(EO_INIT_INSTALLED, EO_CURRENT_VERSION), 'success');
}

// -----
// If the currently-installed version is different from the current version, additional checks and updates ...
//
if (EO_VERSION !== EO_CURRENT_VERSION) {
    switch (true) {
        // -----
        // Next, check for any version-related updates ...
        //
        case (EO_VERSION <= '4.1.1'):
            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'EO_SHIPPING_TAX'
                  LIMIT 1"
            );                                  //-Fall-through for additional checks

        case (version_compare(EO_VERSION, '4.3.0', '<') || !defined('EO_TOTAL_RESET_DEFAULT')):
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Reset Totals on Update &mdash; Default', 'EO_TOTAL_RESET_DEFAULT', 'off', 'Choose the default value for the <em>Reset totals prior to update</em> checkbox.  If your store uses order-total modules that perform tax-related recalculations (like &quot;Group Pricing&quot;), set this value to <b>on</b>.', $cgi, 5, now(), NULL, 'zen_cfg_select_option([\'on\', \'off\'],')"
            );                                  //-Fall-through for additional checks

        case (version_compare(EO_VERSION, '4.3.4', '<') || !defined('EO_PRODUCT_PRICE_CALC_METHOD')):
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Product Price Calculation &mdash; Method', 'EO_PRODUCT_PRICE_CALC_METHOD', 'Auto', 'Choose the <em>method</em> that &quot;EO&quot; uses to calculate product prices when an order is updated, one of:<ol><li><b>Auto</b>: Each product-price is re-calculated.  If your products have attributes, this enables changes to a product\'s attributes to automatically update the associated product-price.</li><li><b>Manual</b>: Each product-price is based on the <b><i>admin-entered price</i></b> for the product.</li><li><b>Choose</b>: The product-price calculation method varies on an order-by-order basis, via the &quot;tick&quot; of a checkbox.  The default method used (<em>Auto</em> vs. <em>Manual</em> is defined by the <em>Product Price Calculation &mdash; Default</em> setting.</li></ol>', $cgi, 20, now(), NULL, 'zen_cfg_select_option([\'Auto\', \'Manual\', \'Choose\'],')"
            );
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ( 'Product Price Calculation &mdash; Default', 'EO_PRODUCT_PRICE_CALC_DEFAULT', 'Auto', 'If the product price-calculation method is <b>Choose</b>, what method should be used as the <em>default</em> method?', $cgi, 24, now(), NULL, 'zen_cfg_select_option([\'Auto\', \'Manual\'],')"
            );                                  //-Fall-through for additional checks

        case (version_compare(EO_VERSION, '4.4.0', '<')):
            if (!$sniffer->field_exists(TABLE_ORDERS, 'shipping_tax_rate')) {
                $db->Execute(
                    "ALTER TABLE " . TABLE_ORDERS . "
                       ADD shipping_tax_rate decimal(15,4) default NULL"
                );
            }

            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'EO_MOCK_SHOPPING_CART'
                  LIMIT 1"
            );

            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_description = 'Choose the <em>method</em> that &quot;EO&quot; uses to calculate product prices when an order is updated, one of:<ol><li><b>Auto</b>: Each product-price is re-calculated &mdash; <em>without</em> using any &quot;specials&quot; pricing.  If your products have attributes, this enables changes to a product\'s attributes to automatically update the associated product-price.</li><li><b>AutoSpecials</b>: Each product-price is re-calculated, as above, but using any &quot;specials&quot; pricing.</li><li><b>Manual</b>: Each product-price is based on the <b><i>admin-entered price</i></b> for the product.</li><li><b>Choose</b>: The product-price calculation method varies on an order-by-order basis, via the &quot;tick&quot; of a checkbox.  The default method used (<em>Auto</em> vs. <em>Manual</em> is defined by the <em>Product Price Calculation &mdash; Default</em> setting.</li></ol>',
                        set_function = 'zen_cfg_select_option([\'Auto\', \'AutoSpecials\', \'Manual\', \'Choose\'],'
                  WHERE configuration_key = 'EO_PRODUCT_PRICE_CALC_METHOD'
                  LIMIT 1"
            );
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET set_function = 'zen_cfg_select_option([\'Auto\', \'AutoSpecials\', \'Manual\'],'
                  WHERE configuration_key = 'EO_PRODUCT_PRICE_CALC_DEFAULT'
                  LIMIT 1"
            );

            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function )
                 VALUES
                    ('Status-history Display Order', 'EO_STATUS_HISTORY_DISPLAY_ORDER', 'Asc', 'Choose the way that <em>Edit Orders</em> displays an order\'s status-history records, either as-recorded (<b>Asc</b>) or most-recent first (<b>Desc</b>).', $cgi, 30, now(), NULL, 'zen_cfg_select_option([\'Asc\', \'Desc\'],'),

                    ('Status-update: Customer Notification Default', 'EO_CUSTOMER_NOTIFICATION_DEFAULT', 'Email', 'Choose the default used for the radio-buttons that identify whether the customer receives notification when a  comment is added to the order.', $cgi, 40, now(), NULL, 'zen_cfg_select_option([\'Email\', \'No Email\', \'Hidden\'],')"
            );
                                                //-Fall-through for additional checks
        case (version_compare(EO_VERSION, '4.5.0', '<')):
            $default_value = (EO_VERSION === '0.0.0') ? 'CSB' : 'CBS';
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES 
                    ('Addresses, Display Order', 'EO_ADDRESSES_DISPLAY_ORDER', '$default_value', 'In what order, left-to-right, should <em>Edit Orders</em> display an order\'s addresses?  Choose <b>CSB</b> to display <em>Customer</em>, <em>Shipping</em> and then <em>Billing</em>; choose <b>CBS</b> to display <em>Customer</em>, <em>Billing</em> and then <em>Shipping</em>.', $cgi, 1, now(), NULL, 'zen_cfg_select_option([\'CSB\', \'CBS\'],')"
            );
                                                //-Fall-through for additional checks
        // -----
        // v4.7.0:
        // - No longer checking for 'missing' files.
        // - Add configuration settings to allow a site to control whether to
        //   - Display the EO-icon on each entry in the orders' listing
        //   - Where to display the EO 'Edit' button on the current order's sidebox in the orders' listing.
        //
        case (version_compare(EO_VERSION, '4.7.0', '<')):
            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'EO_INIT_FILE_MISSING'"
            );
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Show Edit-Order Icon on Orders\' Listing?', 'EO_SHOW_EDIT_ORDER_ICON', 'Yes', 'Should the edit-icon be shown for each order on the orders\' listing?  Default: <b>Yes</b>', $cgi, 50, now(), NULL, 'zen_cfg_select_option([\'Yes\', \'No\'],'),

                    ('Edit Button Location on Sidebox', 'EO_SHOW_EDIT_ORDER_BUTTON', 'Both', 'At which position(s) should the <em>Edit</em> button be displayed on the currently-selected order\'s sidebox display, relative to the order\'s information?  Default: <b>Both</b>', $cgi, 50, now(), NULL, 'zen_cfg_select_option([\'Both\', \'Top Only\', \'Bottom Only\', \'Neither\'],')"
            );                                  //-Fall-through for additional checks
        // -----
        // Way-old versions of EO didn't have the EO_DEBUG_ACTION_LEVEL setting set.
        //
        case (EO_VERSION !== '0.0.0' && !defined('EO_DEBUG_ACTION_LEVEL')):
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                    ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) 
                 VALUES 
                    ( 'Debug Action Level', 'EO_DEBUG_ACTION_LEVEL', '0', 'When enabled when actions are performed by Edit Orders additional debugging information will be stored in a log file.<br><br>Enabling debugging will result in a large number of created log files and may adversely affect server performance. Only enable this if absolutely necessary!', $cgi, 12, now(), NULL, 'eo_debug_action_level_list(')"
            );
                                                //-Fall-through for additional checks

        default:
            break;
    }

    // -----
    // Update the configuration reflect the current EO version, issuing an updated-message if this wasn't an
    // initial install.
    //
    if (EO_VERSION !== '0.0.0') {
        $messageStack->add(sprintf(EO_INIT_VERSION_UPDATED, EO_VERSION, EO_CURRENT_VERSION), 'success');
    }
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . "
            SET configuration_value = '" . EO_CURRENT_VERSION . "',
                set_function = 'zen_cfg_read_only('
          WHERE configuration_key = 'EO_VERSION'
          LIMIT 1"
    );
}

// -----
// If a previous 'run' of EO has saved a pre-existing currency into the session, restore
// that value at this point.
//
if (isset($_SESSION['eo_saved_currency'])) {
    if ($_SESSION['eo_saved_currency'] === false) {
        unset($_SESSION['currency']);
    } else {
        $_SESSION['currency'] = $_SESSION['eo_saved_currency'];
    }
    unset($_SESSION['eo_saved_currency']);
}
