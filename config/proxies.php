<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Which proxies in front of this app are allowed to tell it about the
    | original request — most importantly, whether that request was HTTPS.
    |
    | `herd share`, ngrok, Expose, Cloudflare Tunnel and most hosting platforms
    | terminate TLS at the proxy and forward to the app over plain HTTP. Unless
    | the proxy is trusted, Laravel believes the request really was insecure and
    | builds every absolute URL as http:// — so on an https:// tunnel the
    | browser blocks the stylesheet and Livewire's JavaScript as mixed content,
    | and the panel loads unstyled and inert.
    |
    | Left empty, nothing is trusted and behaviour is exactly as it was, because
    | a forwarded header from an untrusted source can spoof the client IP. Set
    | TRUSTED_PROXIES=* while sharing from Herd, or list specific addresses when
    | deploying behind a known proxy.
    |
    */

    'trusted' => env('TRUSTED_PROXIES'),

];
