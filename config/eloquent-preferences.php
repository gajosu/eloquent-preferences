<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The Preferences Table
    |--------------------------------------------------------------------------
    |
    | The name of the table to store model preferences in, if
    | "model_preferences" doesn't work for your application.
    |
    */

    'table' => 'model_preferences',

    /*
    |--------------------------------------------------------------------------
    | "Hidden" preference columns
    |--------------------------------------------------------------------------
    |
    | The names of the attributes to hide when exporting preferences to JSON.
    |
    */

    'hidden-attributes' => [],

    /*
    |--------------------------------------------------------------------------
    | Enable caching
    |--------------------------------------------------------------------------
    |
    | If caching is enabled, the preferences will be cached for faster
    | retrieval.
    |
    */

    'cache' => [
        'enabled' => false,
        'prefix' => 'eloquent-preferences',
    ]

];
