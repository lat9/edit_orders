<?php
// -----
// Part of the Edit Orders plugin, v4.1.5 or later.  This file defines the level of "sanitization" required by the adminSanitizer function,
// introduced in a patch-level of Zen Cart v1.5.5, so need to make sure that the "proper" version of the class is included.
//
// Last updated: v5.0.3
//
// Copyright (C) 2015-2026, Vinos de Frutas Tropicales
//
if (($_GET['cmd'] ?? '') !== 'edit_orders') {
    return;
}

$eo_sanitizer = AdminRequestSanitizer::getInstance();

$group = [
    'company',
    'street_address',
    'suburb',
    'city',
    'state',
    'postcode',
    'comments',
    'title',
    'model',
];
$eo_sanitizer->addSimpleSanitization('WORDS_AND_SYMBOLS_REGEX', $group);

$group = ['email_address'];
$sanitizer->addSimpleSanitization('SANITIZE_EMAIL', $group);

$group = ['prid'];
$sanitizer->addSimpleSanitization('CONVERT_INT', $group);

$group = ['tax', 'value', 'qty', 'final_price', 'gross_price'];
$sanitizer->addSimpleSanitization('FLOAT_VALUE_REGEX', $group);
