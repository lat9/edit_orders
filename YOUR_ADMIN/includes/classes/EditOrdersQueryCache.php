<?php
// -----
// Part of the Edit Orders plugin (v4.1.7 and later) by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016, Vinos de Frutas Tropicales
//
class EditOrdersQueryCache 
{
    function __construct() 
    {
        $this->queries = array();
    }

    // cache queries if and only if query is 'SELECT' statement
    // returns:
    //	TRUE - if and only if query has been stored in cache
    //	FALSE - otherwise
    // -----
    // For Edit Orders, no caching ...
    //
    function cache ($query, $result) 
    {
        return false;
    }

    function getFromCache ($query) 
    {
        trigger_error ('Invalid call received during Edit Orders processing', E_USER_ERROR);
        exit ();
    }

    function inCache ($query) 
    {
        return false;
    }

    function isSelectStatement($q) 
    {
        return false;
    }

    function reset ($query) 
    {
        if ('ALL' == $query) {
            $this->queries = array();
            return FALSE;
        }
        unset ($this->queries[$query]);
    }
}
