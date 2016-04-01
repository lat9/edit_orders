<?php
define('EO_DESCRIPTION', 'Allows administrative users to edit orders.');

// Configuration Language Files
define('EO_MOCK_SHOPPING_CART_TITLE', 'Use a mock shopping cart?');
define('EO_MOCK_SHOPPING_CART_DESCRIPTION', 'When enabled a mock shopping cart is created which reads product information from the current order. Many of the 3rd party &quot;Discount&quot Order Total modules were not designed to be run from the Zen Cart administrative interface and require this option to be enabled.<br /><br />The mock shopping cart only provides the get_products and in_cart methods.<br /><br /><span="alert">If installed order total or shipping modules require additional methods from the shopping cart, this method should be disabled.</span>');
define('EO_SHIPPING_DROPDOWN_STRIP_TAGS_TITLE', 'Strip tags from the shipping module name?');
define('EO_SHIPPING_DROPDOWN_STRIP_TAGS_DESCRIPTION', 'When enabled HTML and PHP tags present in the title of a shipping module are removed from the text displayed in the shipping dropdown menu.<br /><br /><span="alert">If partial or broken tags are present in the title it may result in the removal of more text than expected. If this happens, you will need to update the affected shipping module(s) or disable this option.</span>');
define('EO_DEBUG_ACTION_LEVEL_TITLE', 'Debug Action Level');
define('EO_DEBUG_ACTION_LEVEL_DESCRIPTION', 'When enabled when actions are performed by Edit Orders additional debugging information will be stored in a log file.<br /><br />Enabling debugging will result in a large number of created log files and may adversely affect server performance. Only enable this if absolutely necessary!');