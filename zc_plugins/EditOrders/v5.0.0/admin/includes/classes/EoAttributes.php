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
 * Class used for managing various product attributes' information
 *
 * @author Andrew Ballanger
 * @package classes
 */
class EoAttributes
{
    protected array $optionsValues;
    protected array $currentSelections;

    /**
     * Constructs an Attributes class for accessing product attributes, options,
     * and values.
     */
    public function __construct(int $products_id, array $selected_attributes = [])
    {
        global $db;

        $this->currentSelections = [];
        foreach ($selected_attributes as $next_attr) {
            $this->currentSelections[$next_attr['option_id']]['value_ids'][] = $next_attr['value_id'];
            $this->currentSelections[$next_attr['option_id']]['value'] = $next_attr['value'];
        }

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
               FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                    INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po
                        ON po.products_options_id = pa.options_id
                       AND po.language_id = :language_id 
              WHERE pa.products_id = :products_id";

        $sql = $db->bindVars($sql, ':products_id', $products_id, 'integer');
        $sql = $db->bindVars($sql, ':language_id', $_SESSION['languages_id'], 'integer');

        // Don't include READONLY attributes if product can be added to cart without them
        if (PRODUCTS_OPTIONS_TYPE_READONLY_IGNORED === '0') {
            $sql .= ' AND po.products_options_type != ' . (int)PRODUCTS_OPTIONS_TYPE_READONLY;
        }

        $sql .= ' ORDER BY ' . $options_order_by;
        $options_result = $db->Execute($sql);

        // -----
        // Preset the "ORDER BY" clause for the products' options' values.
        //
       if (PRODUCTS_OPTIONS_SORT_BY_PRICE === '1') {
            $options_values_order_by = "LPAD(pa.products_options_sort_order, 11, '0'), pov.products_options_values_name";
        } else {
            $options_values_order_by = "LPAD(pa.products_options_sort_order, 11, '0'), pa.options_values_price";
        }

        $this->optionsValues = [];
        foreach ($options_result as $option) {
            $option_id = $option['id'];
            $this->optionsValues[$option_id] = $option;
            $this->optionsValues[$option_id]['values'] = [];

            $sql =
                "SELECT pov.products_options_values_id AS `id`, pov.products_options_values_name AS `name`, pa.*
                   FROM  " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                        INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                            ON pov.products_options_values_id = pa.options_values_id
                           AND pov.language_id = :language_id
                  WHERE pa.products_id = :products_id
                    AND pa.options_id = :options_id
                    AND pa.attributes_display_only = 0
                  ORDER BY " . $options_values_order_by;

            $sql = $db->bindVars($sql, ':products_id', $products_id, 'integer');
            $sql = $db->bindVars($sql, ':options_id', $option_id, 'integer');
            $sql = $db->bindVars($sql, ':language_id', $_SESSION['languages_id'], 'integer');
            $values_result = $db->Execute($sql);
            foreach ($values_result as $option_value) {
                $this->optionsValues[$option_id]['values'][$option_value['id']] = $option_value;
            }
        }

        // Check for the installation of "Absolute's Product Attribute Grid"
        if (!defined('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID')) {
            if (defined('CONFIG_ATTRIBUTE_OPTION_GRID_INSTALLED')) {
                define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', '23997');
            } else {
                define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', '-1');
            }
        }

        // Check for the installation of "Potteryhouse's/mc12345678's Stock By Attributes"
        zen_define_default('PRODUCTS_OPTIONS_TYPE_SELECT_SBA', '-1');

        // -----
        // Check for the installation of lat9's "Attribute Image Swapper".
        //
        zen_define_default('PRODUCTS_OPTIONS_TYPE_IMAGE_SWATCH', -1);
    }

    public function getOptionsValues(): array
    {
        return $this->optionsValues;
    }

    public function getOptionCurrentValueId(int $option_id): null|string
    {
        return $this->currentSelections[$option_id]['value_ids'][0] ?? null;
    }

    public function getOptionCurrentValue(int $option_id): null|string
    {
        return $this->currentSelections[$option_id]['value'] ?? null;
    }

    public function isOptionValueSelected(int $option_id, string $option_value_id): bool
    {
        return isset($this->currentSelections[$option_id . '_chk' . $option_value_id]) || in_array($option_value_id, $this->currentSelections[$option_id]['value_ids'] ?? []);
    }

    public function searchOptionsValues(string $key, string $value): array
    {
        return $this->search($this->optionsValues, $key, $value);
    }

    // -----
    // Derived from https://www.geeksforgeeks.org/how-to-search-by-keyvalue-in-a-multidimensional-array-in-php/
    //
    // Recursively search for a given key => value.
    //
    protected function search(array $array, string $key, string $value): array
    {
        $results = [];

        if (is_array($array)) {
            // if array has required key and value matched, store result 
            if (isset($array[$key]) && $array[$key] === $value) {
                $results[] = $array;
            }

            // Iterate for each element in array
            foreach ($array as $subarray) {
                $results = array_merge($results, $this->search($subarray, $key, $value));
            }
        }

        return $results;
    }
}
