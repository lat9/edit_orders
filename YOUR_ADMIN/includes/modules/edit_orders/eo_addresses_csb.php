<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019, Vinos de Frutas Tropicales.
//
// This module is loaded in global scope by /admin/edit_orders.php when the store has identified via
// configuration that the address-order is Customer/Shipping/Billing.
//
?>
                            <tr>
                                <td>&nbsp;</td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER; ?></td>
                                <td>&nbsp;</td>
                                <td class="eo-label"><?php echo ENTRY_SHIPPING_ADDRESS; ?></td>
                                <td>&nbsp;</td>
                                <td class="eo-label"><?php echo ENTRY_BILLING_ADDRESS; ?></td>

                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td><?php echo zen_image(DIR_WS_IMAGES . 'icon_customers.png', ENTRY_CUSTOMER); ?></td>
                                <td>&nbsp;</td>
                                <td><?php echo zen_image(DIR_WS_IMAGES . 'icon_shipping.png', ENTRY_SHIPPING_ADDRESS); ?></td>
                                <td>&nbsp;</td>
                                <td><?php echo zen_image(DIR_WS_IMAGES . 'icon_billing.png', ENTRY_BILLING_ADDRESS); ?></td>
                            </tr>

                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</td>
                                <td><input name="update_customer_name" size="45" value="<?php echo zen_db_output($order->customer['name']); ?>" <?php echo $max_name_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</td>
                                <td><input name="update_delivery_name" size="45" value="<?php echo zen_db_output($order->delivery['name']); ?>" <?php echo $max_name_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</td>
                                <td><input name="update_billing_name" size="45" value="<?php echo zen_db_output($order->billing['name']); ?>" <?php echo $max_name_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</td>
                                <td><input name="update_customer_company" size="45" value="<?php echo zen_db_output($order->customer['company']); ?>" <?php echo $max_company_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</td>
                                <td><input name="update_delivery_company" size="45" value="<?php echo zen_db_output($order->delivery['company']); ?>" <?php echo $max_company_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</td>
                                <td><input name="update_billing_company" size="45" value="<?php echo zen_db_output($order->billing['company']); ?>" <?php echo $max_company_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</td>
                                <td><input name="update_customer_street_address" size="45" value="<?php echo zen_db_output($order->customer['street_address']); ?>" <?php echo $max_street_address_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</td>
                                <td><input name="update_delivery_street_address" size="45" value="<?php echo zen_db_output($order->delivery['street_address']); ?>" <?php echo $max_street_address_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</td>
                                <td><input name="update_billing_street_address" size="45" value="<?php echo zen_db_output($order->billing['street_address']); ?>" <?php echo $max_street_address_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</td>
                                <td><input name="update_customer_suburb" size="45" value="<?php echo zen_db_output($order->customer['suburb']); ?>" <?php echo $max_suburb_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</td>
                                <td><input name="update_delivery_suburb" size="45" value="<?php echo zen_db_output($order->delivery['suburb']); ?>" <?php echo $max_suburb_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</td>
                                <td><input name="update_billing_suburb" size="45" value="<?php echo zen_db_output($order->billing['suburb']); ?>" <?php echo $max_suburb_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</td>
                                <td><input name="update_customer_city" size="45" value="<?php echo zen_db_output($order->customer['city']); ?>" <?php echo $max_city_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</td>
                                <td><input name="update_delivery_city" size="45" value="<?php echo zen_db_output($order->delivery['city']); ?>" <?php echo $max_city_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</td>
                                <td><input name="update_billing_city" size="45" value="<?php echo zen_db_output($order->billing['city']); ?>" <?php echo $max_city_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</td>
                                <td><input name="update_customer_state" size="45" value="<?php echo zen_db_output($order->customer['state']); ?>" <?php echo $max_state_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</td>
                                <td><input name="update_delivery_state" size="45" value="<?php echo zen_db_output($order->delivery['state']); ?>" <?php echo $max_state_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</td>
                                <td><input name="update_billing_state" size="45" value="<?php echo zen_db_output($order->billing['state']); ?>" <?php echo $max_state_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</td>
                                <td><input name="update_customer_postcode" size="45" value="<?php echo zen_db_output($order->customer['postcode']); ?>" <?php echo $max_postcode_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</td>
                                <td><input name="update_delivery_postcode" size="45" value="<?php echo zen_db_output($order->delivery['postcode']); ?>" <?php echo $max_postcode_length; ?>></td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</td>
                                <td><input name="update_billing_postcode" size="45" value="<?php echo zen_db_output($order->billing['postcode']); ?>" <?php echo $max_postcode_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</td>
                                <td>
<?php
    if (is_array($order->customer['country']) && isset($order->customer['country']['id'])) {
        echo zen_get_country_list('update_customer_country', $order->customer['country']['id']);
    } else {
        echo '<input name="update_customer_country" size="45" value="' . zen_db_output($order->customer['country']) . '"' . $max_country_length . '">';
    } 
?>
                                </td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</td>
                                <td>
<?php
    if (is_array($order->delivery['country']) && isset($order->delivery['country']['id'])) {
        echo zen_get_country_list('update_delivery_country', $order->delivery['country']['id']);
    } else {
        echo '<input name="update_delivery_country" size="45" value="' . zen_db_output($order->delivery['country']) . '"' . $max_country_length . '">';
    } 
?>
                                </td>
                                <td class="eo-label"><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</td>
                                <td>
<?php
    if (is_array($order->billing['country']) && isset($order->billing['country']['id'])) {
        echo zen_get_country_list('update_billing_country', $order->billing['country']['id']);
    } else {
        echo '<input name="update_billing_country" size="45" value="' . zen_db_output($order->billing['country']) . '"' . $max_country_length . '">';
    } 
?>
                                </td>
                            </tr>
