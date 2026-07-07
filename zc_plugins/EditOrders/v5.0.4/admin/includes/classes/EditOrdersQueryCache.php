<?php
// -----
// Part of the Edit Orders plugin by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016-2024, Vinos de Frutas Tropicales
//
// Last updated: v5.0.0
//
namespace Zencart\Plugins\Admin\EditOrders;

class EditOrdersQueryCache
{
    protected
        $queries;

    function __construct()
    {
        $this->queries = [];
    }

    // cache queries if and only if query is 'SELECT' statement
    // returns:
    //	TRUE - if and only if query has been stored in cache
    //	FALSE - otherwise
    // -----
    // For Edit Orders, no caching ...
    //
    function cache($query, $result)
    {
        return false;
    }

    function getFromCache($query)
    {
        trigger_error('Invalid call received during Edit Orders processing', E_USER_ERROR);
        exit();
    }

    function inCache($query)
    {
        return false;
    }

    function isSelectStatement($q)
    {
        return false;
    }

    function reset($query)
    {
        if ('ALL' === $query) {
            $this->queries = [];
            return false;
        }
        unset($this->queries[$query]);
    }
}
