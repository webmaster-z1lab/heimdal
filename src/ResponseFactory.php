<?php

namespace Optimus\Heimdal;

use Throwable;
use Illuminate\Http\JsonResponse;

class ResponseFactory
{
    /**
     * @param  \Throwable  $e
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function make(Throwable $e)
    {
        return new JsonResponse([
            'status' => 'error',
        ]);
    }
}
