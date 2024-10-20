<?php
// -----
// Part of the "Edit Orders" plugin for Zen Cart.
//
//-Last modified: EO v5.0.0
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Class used for managing various product information
 *
 * @author Andrew Ballanger
 * @package classes
 */
class attributes extends base
{
    protected string $options_order_by,
    protected string $options_values_order_by;

    /**
     * Constructs an Attributes class for accessing product attributes, options,
     * and values.
     */
    public function __construct()
    {
        // -----
        // Preset the "ORDER BY" clause for the products' options.
        //
        if (PRODUCTS_OPTIONS_SORT_ORDER === '0') {
            $this->options_order_by = "LPAD(po.products_options_sort_order, 11, '0'), po.products_options_name";
        } else {
            $this->options_order_by = 'po.products_options_name';
        }

        // -----
        // Preset the "ORDER BY" clause for the products' options' values.
        //
       if (PRODUCTS_OPTIONS_SORT_BY_PRICE === '1') {
            $this->options_values_order_by = "LPAD(pa.products_options_sort_order, 11, '0'), pov.products_options_values_name";
        } else {
            $this->options_values_order_by = "LPAD(pa.products_options_sort_order, 11, '0'), pa.options_values_price";
        }
    }

    /**
     * Returns a multidimensional array containing the product attribute options
     * id / name / value rows for the specified product.
     *
     * @param int|string $zf_product_id the specified product id.

     * @return array
     */
    public function get_attributes_options($zf_product_id)
    {
        global $db;
        $query =
            "SELECT pa.products_attributes_id, pa.options_id AS `id`,
                    po.products_options_name AS `name`, po.products_options_type AS `type`,
                    po.products_options_size AS `size`, po.products_options_rows AS `rows`,
                    po.products_options_length AS `length`, pov.products_options_values_name as `value`
               FROM " . TABLE_PRODUCTS_ATTRIBUTES . " AS pa
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " AS po
                        ON pa.options_id = po.products_options_id
                       AND po.language_id = " . (int)$_SESSION['languages_id'] . "
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS pov
                        ON pa.options_values_id = pov.products_options_values_id
                       AND pov.language_id = po.language_id
              WHERE pa.products_id = " . (int)$zf_product_id;
 
        // Don't include READONLY attributes if product can be added to cart without them
        if (PRODUCTS_OPTIONS_TYPE_READONLY_IGNORED === '0') {
            $query .= " AND po.products_options_type != " . (int)PRODUCTS_OPTIONS_TYPE_READONLY;
        }

        $query .= ' ORDER BY ' . $this->options_order_by . ', ' . $this->options_values_order_by;

        $queryResult = $db->Execute($query);

        $retval = [];
        foreach ($queryResult as $result) {
            $retval[$result['products_attributes_id']] = $result;
        }
        return $retval;
    }

    /**
     * Returns a multidimensional array containing product attribute information. This method
     * allows you to specify a key format to change the names of the keys in the
     * returned array.
     * @param int|string $zf_product_id the specified product id.
     * @param int|string $zf_option_id the specified option id.
     * @return array
     */
    public function get_attributes_by_option($zf_product_id, $zf_option_id)
    {
        global $db;
        $query =
            "SELECT pa.*, po.products_options_name, pov.products_options_values_name, po.products_options_type
               FROM " . TABLE_PRODUCTS_ATTRIBUTES . " AS pa
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " AS po
                        ON pa.options_id = po.products_options_id
                       AND po.language_id = " . (int)$_SESSION['languages_id'] . "
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS pov
                        ON pa.options_values_id = pov.products_options_values_id
                       AND pov.language_id = po.language_id
              WHERE pa.products_id = " . (int)$zf_product_id . "
                AND pa.options_id = " . (int)$zf_option_id . "
           ORDER BY " . $this->options_values_order_by;

        $queryResult = $db->Execute($query);

        $retval = [];
        foreach ($queryResult as $result) {
            $retval[] = zen_db_prepare_input($result);
        }
        return $retval;
    }

    /**
     * Returns an array containing product attribute information. This method
     * allows you to specify a key format to change the names of the keys in the
     * returned array. Currently supported formats are:
     * 'database': Uses the keys found in the database.
     * 'order': Uses the keys used when adding a product to an order.
     *
     * @param int|string $zf_attribute_id the specified product id.
     * @param string $key_format the specified key format for the array
     * @return array
     */
    public function get_attribute_by_id($zf_attribute_id, $key_format = 'database')
    {
        global $db;
        $query =
            "SELECT pa.*, po.products_options_name, pov.products_options_values_name AS value, po.products_options_type
               FROM " . TABLE_PRODUCTS_ATTRIBUTES . " AS pa
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " AS po
                        ON pa.options_id = po.products_options_id
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS pov
                        ON pa.options_values_id = pov.products_options_values_id
              WHERE pa.products_attributes_id = " . (int)$zf_attribute_id . "
                AND pov.language_id = " . (int)$_SESSION['languages_id'] . "
                AND pov.language_id = po.language_id
              LIMIT 1";
        $queryResult = $db->Execute($query);

        $retval = [];
        if (!$queryResult->EOF) {
            if ($key_format == 'order') {
                $retval = [
                    'option_id' => $queryResult->fields['options_id'],
                    'value_id' => $queryResult->fields['options_values_id'],
                    'value' => $queryResult->fields['value'],
                ];
            } else {
                $retval = zen_db_prepare_input($queryResult->fields);
                $retval['option_id'] = $queryResult->fields['options_id'];
                $retval['value_id'] = $queryResult->fields['options_values_id'];
            }
        }
        return $retval;
    }
}
