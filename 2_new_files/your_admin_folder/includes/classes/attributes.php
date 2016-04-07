<?php
if (!defined('IS_ADMIN_FLAG')) {
	die('Illegal Access');
}
include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.base.php');

/**
 * Class used for managing various product information
 *
 * @author Andrew Ballanger
 * @package classes
 */
class attributes extends base {

	/**
	 * Sort attributes, options, and values using the Zen Cart default sorting method.
	 *
	 * @var int
	 */
	public static $PRODUCTS_ATTRIBUTES_SORT_DEFAULT = 0;
	/**
	 * Sort attributes, options, and values by the options "sort order" and then
	 * by attribute "id".
	 *
	 * @var int
	 */
	public static $PRODUCTS_ATTRIBUTES_SORT_ORDER_ID = 1;
	/**
	 * Do not sort attributes, options, and values.
	 *
	 * @var int
	 */
	public static $PRODUCTS_ATTRIBUTES_SORT_NONE = 2;
	// Length of time to cache attribute information
	private $cache_time;

	// Sort mode in use by this class
	private $sort_mode;
	/**
	 * Constructs an Attributes class for accessing product attributes, options,
	 * and values.
	 *
	 * @param int $cache_time the length of time to cache SQL queries. This
	 *     option only has an effect if Zen Cart is configured to cache SQL
	 *     Queries.
	 *
	 * @param int $sort_mode the mode to use when sorting attributes.
	 * @see Attributes::$PRODUCTS_ATTRIBUTES_SORT_DEFAULT
	 * @see Attributes::$PRODUCTS_ATTRIBUTES_SORT_ORDER_ID
	 * @see Attributes::$PRODUCTS_ATTRIBUTES_SORT_NONE
	 */
	public function __construct($cache_time = null, $sort_mode = null) {
		$this->cache_time = 43200; // 12 hours (43200 seconds)
		if($cache_time !== null) {
			$this->cache_time = (int)$cache_time;
		}
		switch($sort_mode) {
			case Attributes::$PRODUCTS_ATTRIBUTES_SORT_DEFAULT:
			case Attributes::$PRODUCTS_ATTRIBUTES_SORT_ORDER_ID:
			case Attributes::$PRODUCTS_ATTRIBUTES_SORT_NONE:
				$this->sort_mode = (int)$sort_mode;
				break;
			default:
				$this->sort_mode = Attributes::$PRODUCTS_ATTRIBUTES_SORT_DEFAULT;
		}
	}
	/**
	 * Returns a multidimensional array containing product attribute options
	 * option id / value name for the specified product
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param int|string $options_type the option type to list (defaults to read only).
	 * @return array
	 */
	function get_attribute_options_values_by_type($zf_product_id, $options_type = PRODUCTS_OPTIONS_TYPE_READONLY) {
		global $db;
		$query = 'SELECT attr.products_attributes_id, attr.products_id, attr.options_id, val.products_options_values_name ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
			'WHERE opt.products_options_type = \'' . (int)$options_type . '\' ' .
				'AND attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
			$this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while (!$queryResult->EOF) {
			$retval[$queryResult->fields['products_attributes_id']] = array(
				'id' => $queryResult->fields['options_id'],
				'value' => $queryResult->fields['products_options_values_name']
			);
			$queryResult->MoveNext();
		}
		return $retval;
	}

	/**
	 * Returns a multidimensional array containing product attribute options
	 * value names for the specified product id and option id
	 * sorted by option id.
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param int|string $options_id the option id to list.
	 * @return array
	 */
	function get_attribute_options_values_by_option($zf_product_id, $options_id) {
		global $db;
		$query = 'SELECT attr.products_attributes_id, attr.products_id, attr.options_id, val.products_options_values_name ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
			'WHERE attr.options_id = \'' . (int)$options_id . '\' ' .
				'AND attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
			$this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while (!$queryResult->EOF) {
			$retval[$queryResult->fields['products_attributes_id']] = array(
				'id' => $queryResult->fields['options_id'],
				'value' => $queryResult->fields['products_options_values_name']
			);
			$queryResult->MoveNext();
		}
		return $retval;
	}

	/**
	 * Returns a multidimensional array containing the product attribute options
	 * id / name pairs for the specified product by type.
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param int|string $options_type the option type to list (defaults to read only).
	 * @return array
	 */
	function get_attribute_options_names_by_type($zf_product_id, $options_type = PRODUCTS_OPTIONS_TYPE_READONLY) {
		global $db;
		$query = 'SELECT attr.products_attributes_id, attr.products_id, attr.options_id, opt.products_options_name ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'WHERE opt.products_options_type = \'' . (int)$options_type . '\' ' .
				'AND attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND opt.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
			$this->getSortOrderSQL(false);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while (!$queryResult->EOF) {
			$retval[$queryResult->fields['products_attributes_id']] = array(
				'id' => $queryResult->fields['options_id'],
				'name' => $queryResult->fields['products_options_name']
			);
			$queryResult->MoveNext();
		}
		return $retval;
	}

	/**
	* Returns the product attribute options name for the specified option_id.
	*
	* @param int|string $options_id the option id to lookup.
	* @return string|null name of the attribute or null if no name.
	*/
	function get_attribute_options_name_by_option($options_id) {
		global $db;
		$query = 'SELECT opt.products_options_name ' .
			'FROM ' . TABLE_PRODUCTS_OPTIONS . ' AS opt ' .
			'WHERE opt.products_options_id = \'' . (int)$options_id . '\' ' .
				'AND opt.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
			'LIMIT 1';

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		if(!$queryResult->EOF) {
			return $queryResult->fields['products_options_name'];
		}
		return null;
	}

	/**
	 * Returns a multidimensional array containing the product attribute options
	 * id / name / value rows for the specified product.
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param int|string $options_type the option type to list (defaults to read only).
	 * @return array
	 */
	function get_attributes_options_by_type($zf_product_id, $options_type = PRODUCTS_OPTIONS_TYPE_READONLY) {
		global $db;
		$query = 'SELECT attr.products_attributes_id, attr.products_id, attr.options_id, opt.products_options_name, val.products_options_values_name ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
			'WHERE opt.products_options_type = \'' . (int)$options_type . '\' ' .
				'AND attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND val.language_id = opt.language_id ' .
			$this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while (!$queryResult->EOF) {
			$retval[$queryResult->fields['products_attributes_id']] = array(
				'id' => $queryResult->fields['options_id'],
				'name' => $queryResult->fields['products_options_name'],
				'value' => $queryResult->fields['products_options_values_name']
			);
			$queryResult->MoveNext();
		}
		return $retval;
	}

	/**
	 * Returns a multidimensional array containing the product attribute options
	 * id / name / value rows for the specified product.
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param bool $readonly include readonly attributes not required to add a
	 *        product to the cart, defaults to false.
	 * @return array
	 */
	function get_attributes_options($zf_product_id, $readonly = false) {
		global $db;
		$query = 'SELECT attr.products_attributes_id, attr.products_id, attr.options_id, opt.products_options_name, val.products_options_values_name, opt.products_options_type, products_options_size, opt.products_options_rows ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
			'WHERE attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND val.language_id = opt.language_id ';

		// Don't include READONLY attributes if product can be added to cart without them
		if(PRODUCTS_OPTIONS_TYPE_READONLY_IGNORED == '1' && $readonly === false) {
			$query .= 'AND opt.products_options_type != \'' . PRODUCTS_OPTIONS_TYPE_READONLY . '\' ';
		}

		$query .= $this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while (!$queryResult->EOF) {
			$retval[$queryResult->fields['products_attributes_id']] = array(
				'id' => $queryResult->fields['options_id'],
				'name' => $queryResult->fields['products_options_name'],
				'value' => $queryResult->fields['products_options_values_name'],
				'type' => $queryResult->fields['products_options_type'],
				'length' => $queryResult->fields['products_options_length'],
				'size' => $queryResult->fields['products_options_size'],
				'rows' => $queryResult->fields['products_options_rows']
			);
			$queryResult->MoveNext();
		}
		return $retval;
	}

	/**
	 * Returns a multidimensional array containing product attribute information
	 * with the product_attribute_id as key. The attribute information fields in
	 * the second level of the array will already be passed through zen_db_prepare_input
	 * to "clean" the values.
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param bool $readonly include readonly attributes not required to add a
	 *        product to the cart, defaults to false.
	 * @return array
	 */
	function get_attributes($zf_product_id, $readonly = false) {
		global $db;
		$query = 'SELECT attr.*, opt.products_options_name, val.products_options_values_name, opt.products_options_type ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
			'WHERE attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND val.language_id = opt.language_id ';

		// Don't include READONLY attributes if product can be added to cart without them
		if(PRODUCTS_OPTIONS_TYPE_READONLY_IGNORED == '1' && $readonly === false) {
			$query .= 'AND opt.products_options_type != \'' . PRODUCTS_OPTIONS_TYPE_READONLY . '\' ';
		}

		$query .= $this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while (!$queryResult->EOF) {
			$id = $queryResult->fields['products_attributes_id'];
			unset($queryResult->fields['products_attributes_id']);
			foreach($queryResult->fields as $key => $value) {
				$retval[$id][$key] = zen_db_prepare_input($value);
			}

			$queryResult->MoveNext();
		}
		return $retval;
	}

	/**
	 * Returns a multidimensional array containing product attribute information. This method
	 * allows you to specify a key format to change the names of the keys in the
	 * returned array. Currently supported formats are:<br />
	 * <strong>database</strong>: Uses the keys found in the database.<br />
	 * <strong>order</strong>: Uses the keys used when adding a product to an order.<br />
	 *
	 * @param int|string $zf_product_id the specified product id.
	 * @param int|string $zf_option_id the specified option id.
	 * @param string $key_format the specified key format for the array
	 * @return array
	 */
	function get_attributes_by_option($zf_product_id, $zf_option_id, $key_format = 'database') {
		global $db;
		$query = 'SELECT attr.*, opt.products_options_name, val.products_options_values_name, opt.products_options_type ' .
			'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
			'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
			'WHERE attr.products_id = \'' . (int)$zf_product_id . '\' ' .
				'AND attr.options_id = \'' . (int)$zf_option_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND val.language_id = opt.language_id' .
			$this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		while(!$queryResult->EOF) {
			$tmp = array();
			switch($key_format) {
				case 'order':
					$tmp = array(
						'option_id' => $queryResult->fields['options_id'],
						'value_id' => $queryResult->fields['options_values_id'],
						'value' => $queryResult->fields['products_options_values_name'],
					);
					if($queryResult->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT) {
						unset($tmp['value']); // Remove value if type text
						$tmp['attr_id'] = $queryResult->fields['products_attributes_id'];
					}
					break;
				case 'database':
				default:
					foreach($queryResult->fields as $key => $value) {
						$tmp[$key] = zen_db_prepare_input($value);
					}
					unset($key); unset($value);
					break;
			}
			$retval[] = $tmp;

			$queryResult->MoveNext();
		}

		return $retval;
	}

	/**
	 * Returns an array containing product attribute information. This method
	 * allows you to specify a key format to change the names of the keys in the
	 * returned array. Currently supported formats are:<br />
	 * <strong>database</strong>: Uses the keys found in the database.<br />
	 * <strong>order</strong>: Uses the keys used when adding a product to an order.<br />
	 *
	 * @param int|string $zf_attribute_id the specified product id.
	 * @param string $key_format the specified key format for the array
	 * @return array
	 */
	function get_attribute_by_id($zf_attribute_id, $key_format = 'database') {
		global $db;
		$query = 'SELECT attr.*, opt.products_options_name, val.products_options_values_name, opt.products_options_type ' .
				'FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' AS attr ' .
				'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS .
				' AS opt ON attr.options_id = opt.products_options_id ' .
				'LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES .
				' AS val ON attr.options_values_id = val.products_options_values_id ' .
				'WHERE attr.products_attributes_id = \'' . (int)$zf_attribute_id . '\' ' .
				'AND val.language_id = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND val.language_id = opt.language_id' .
			$this->getSortOrderSQL(true);

		if($this->cache_time == 0) $queryResult = $db->Execute($query);
		else $queryResult = $db->Execute($query, false, true, $this->cache_time);

		$retval = array();
		if(!$queryResult->EOF) {
			switch($key_format) {
				case 'order':
					$retval = array(
						'option_id' => $queryResult->fields['options_id'],
						'value_id' => $queryResult->fields['options_values_id'],
						'value' => $queryResult->fields['products_options_values_name'],
					);

					break;
				case 'database':
				default:
					foreach($queryResult->fields as $key => $value) {
						$retval[$key] = zen_db_prepare_input($value);
					}
					unset($key); unset($value);
					break;
			}

			$queryResult->MoveNext();
		}

		return $retval;
	}
	/**
	 * Get the sort order SQL clause to sort database queries returning
	 * attributes, options, and values from the database.
	 *
	 * @param bool $include_values. If this is set to true option value names
	 *     are included in the SQL lookup. Set this to false otherwise.
	 *     Default is false.
	 *
	 * @return string the SQL clause for sorting
	 */
	protected function getSortOrderSQL($include_values = false) {
		$retval = '';
		switch($this->sort_mode) {
			case Attributes::$PRODUCTS_ATTRIBUTES_SORT_ORDER_ID:
				$retval = ' ORDER BY `opt`.`products_options_sort_order`, `attr`.`options_id`';
			case Attributes::$PRODUCTS_ATTRIBUTES_SORT_NONE:
				break;
			case Attributes::$PRODUCTS_ATTRIBUTES_SORT_DEFAULT:
			default:
				if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
					$retval = ' ORDER BY LPAD(`opt`.`products_options_sort_order`,11,"0")';
				} else {
					$retval = ' ORDER BY `opt`.`products_options_name`';
				}
				if (PRODUCTS_OPTIONS_SORT_BY_PRICE == '1') {
					$retval .= ', LPAD(`attr`.`products_options_sort_order`,11,"0")';
					if($include_values) $retval .= ', `val`.`products_options_values_name`';
				} else {
					$retval .= ', LPAD(`attr`.`products_options_sort_order`,11,"0"), `attr`.`options_values_price`';
				}
		}
		return $retval;
	}
}