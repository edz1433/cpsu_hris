<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    |
    | Keys that identify this server to browser push services. Generate them
    | once with `php artisan webpush:vapid` and keep them stable: changing
    | the keys invalidates every existing subscription.
    |
    | Push is disabled while either key is empty.
    |
    */

    'public_key' => env('VAPID_PUBLIC_KEY'),

    'private_key' => env('VAPID_PRIVATE_KEY'),

    // A mailto: or https: contact the push services can reach if something goes wrong.
    // Apple's push service rejects http:// and localhost subjects.
    'subject' => env('VAPID_SUBJECT') ?: (env('MAIL_FROM_ADDRESS') ? 'mailto:' . env('MAIL_FROM_ADDRESS') : env('APP_URL')),

    // How long (seconds) a push service keeps a message for an offline device.
    'ttl' => (int) env('WEBPUSH_TTL', 86400),

];
