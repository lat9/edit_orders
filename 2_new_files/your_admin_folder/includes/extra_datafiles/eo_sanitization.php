<?php
// -----
// Part of the Edit Orders plugin, v4.1.4a or later.  This file defines the level of "sanitization" required by the adminSanitizer function,
// introduced in a patch-level of Zen Cart v1.5.5, so need to make sure that the "proper" version of the class is included.
//
if (defined ('PROJECT_VERSION_MAJOR') && version_compare (PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR, '1.5.5', '>=') && class_exists ('AdminRequestSanitizer') && method_exists ('AdminRequestSanitizer', 'getInstance')) {
    $eo_sanitizer = AdminRequestSanitizer::getInstance();
    $eo_group = array(
        'update_products' => array(
            'sanitizerType' => 'MULTI_DIMENSIONAL',
            'method' => 'post',
            'pages' => array('edit_orders'),
            'params' => array(
                'update_products' => array('sanitizerType' => 'CONVERT_INT'),
                'qty' => array('sanitizerType' => 'FLOAT_VALUE_REGEX'),
                'name' => array('sanitizerType' => 'PRODUCT_DESC_REGEX'),
                'onetime_charges' => array('sanitizerType' => 'FLOAT_VALUE_REGEX'),
                'attr' => array(
                    'sanitizerType' => 'MULTI_DIMENSIONAL',
                    'params' => array(
                        'attr' => array('sanitizerType' => 'CONVERT_INT'),
                        'value' => array('sanitizerType' => 'PRODUCT_DESC_REGEX'),
                        'type' => array('sanitizerType' => 'CONVERT_INT')
                    )
                ),
                'model' => array('sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'),
                'tax' => array('sanitizerType' => 'FLOAT_VALUE_REGEX'),
                'final_price' => array('sanitizerType' => 'FLOAT_VALUE_REGEX'),
            )
        )
    );
    $eo_sanitizer->addComplexSanitization ($eo_group);
}