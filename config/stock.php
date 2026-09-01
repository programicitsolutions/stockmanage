<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Negative stock
    |--------------------------------------------------------------------------
    |
    | Present stock is calculated from the transaction ledger. Stock-out and
    | adjustment-out entries are rejected when they would take a product below
    | zero, unless this flag is enabled by a future business rule.
    |
    */

    'allow_negative_stock' => (bool) env('STOCK_ALLOW_NEGATIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Quantity and money precision
    |--------------------------------------------------------------------------
    */

    'quantity_scale' => 3,
    'money_scale' => 2,

];
