<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Fee
    |--------------------------------------------------------------------------
    |
    | The percentage fee that the platform takes from each transaction.
    | Default is 10% (0.10)
    |
    */

    'platform_fee_percentage' => env('PLATFORM_FEE_PERCENTAGE', 10),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for all transactions.
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Auto Payout
    |--------------------------------------------------------------------------
    |
    | Automatically create payout for provider after booking completion.
    | If false, payouts must be manually processed by admin.
    |
    */

    'auto_payout' => env('AUTO_PAYOUT', false),

    /*
    |--------------------------------------------------------------------------
    | Payout Delay
    |--------------------------------------------------------------------------
    |
    | Number of days to wait after booking completion before releasing payout.
    | This allows time for disputes/refunds.
    |
    */

    'payout_delay_days' => env('PAYOUT_DELAY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Refund Window
    |--------------------------------------------------------------------------
    |
    | Number of days after booking completion that refunds can be requested.
    |
    */

    'refund_window_days' => env('REFUND_WINDOW_DAYS', 14),

];