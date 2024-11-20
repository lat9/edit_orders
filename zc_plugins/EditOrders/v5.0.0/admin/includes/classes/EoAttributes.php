<?php
// -----
// Part of the "Edit Orders" plugin for Zen Cart.
//
// Last modified: EO v5.0.0
//
namespace Zencart\Plugins\Admin\EditOrders;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Class used for managing various product information
 *
 * @author Andrew Ballanger
 * @package classes
 */
class EoAttributes
{
    protected array $optionsValues;

    /**
     * Constructs an Attributes class for accessing product attributes, options,
     * and values.
     */
    public function __construct(int $products_id): void
    {
        global $db;

        // -----
        // Preset the "ORDER BY" clause for the products' options.
        //
        if (PRODUCTS_OPTIONS_SORT_ORDER === '0') {
            $options_order_by = "LPAD(po.products_options_sort_order, 11, '0'), po.products_options_name";
        } else {
            $options_order_by = 'po.products_options_name';
        }

        $sql =
            "SELECT DISTINCT po.products_options_id AS `id`, po.products_options_name AS `name`,
                    po.products_options_type AS `type`, po.products_options_length AS `length`,
                    po.products_options_size AS `size`, po.products_options_rows AS `rows`
               FROM " . TABLE_PRODUCTS_OPTIONS . " po
                    LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                        ON pa.options_id = po.products_options_id
              WHERE pa.products_id= :products_id
                AND po.language_id = :language_id ";
        $sql = $db->bindVars($sql, ':products_id', $products_id, 'integer');
        $sql = $db->bindVars($sql, ':language_id', $_SESSION['languages_id'], 'integer');

        // Don't include READONLY attributes if product can be added to cart without them
        if (PRODUCTS_OPTIONS_TYPE_READONLY_IGNORED === '0') {
            $sql .= " AND po.products_options_type != " . (int)PRODUCTS_OPTIONS_TYPE_READONLY;
        }

        $sql .= ' ORDER BY ' . $options_order_by . ', ' . $options_values_order_by;
        $optionsResult = $db->Execute($sql);

        // -----
        // Preset the "ORDER BY" clause for the products' options' values.
        //
       if (PRODUCTS_OPTIONS_SORT_BY_PRICE === '1') {
            $options_values_order_by = "LPAD(pa.products_options_sort_order, 11, '0'), pov.products_options_values_name";
        } else {
            $options_values_order_by = "LPAD(pa.products_options_sort_order, 11, '0'), pa.options_values_price";
        }

        $this->optionsValues = [];
        foreach ($optionsResult as $option) {
            $option_id = $option['id'];
            $this->optionsValues[$id] = $option;
            $this->optionsValues[$id]['values'] = [];

            $sql =
                "SELECT pov.products_options_values_id AS `id`, pov.products_options_values_name AS `name`, pa.*
                   FROM  " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                        LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                            ON pa.options_values_id = pov.products_options_values_id
                           AND pov.language_id = :language_id
                  WHERE pa.products_id = :products_id
                    AND pa.options_id = :options_id " . $options_values_order_by;

            $sql = $db->bindVars($sql, ':products_id', $_GET['products_id'], 'integer');
            $sql = $db->bindVars($sql, ':options_id', $products_options_id, 'integer');
            $sql = $db->bindVars($sql, ':language_id', $_SESSION['languages_id'], 'integer');
            $valuesResult = $db->Execute($sql);
            foreach ($valuesResult as $option_value) {
                $this->optionsValues[$id]['values'][$option_value['id']] = $option_value
            }
        }
    }
}
