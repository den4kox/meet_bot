<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook',
        '/528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook',
    ];
}
