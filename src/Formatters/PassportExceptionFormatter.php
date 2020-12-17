<?php

namespace Optimus\Heimdal\Formatters;

use Exception;
use Illuminate\Http\JsonResponse;
use Optimus\Heimdal\ErrorObject;

class PassportExceptionFormatter extends BaseFormatter
{
    /**
     * @param  \Illuminate\Http\JsonResponse  $response
     * @param  \Exception                     $e
     * @param  array                          $reporterResponses
     */
    public function format(JsonResponse $response, Exception $e, array $reporterResponses): void
    {
        /** @var \Laravel\Passport\Exceptions\OAuthServerException $e */
        $response->setStatusCode($e->statusCode());

        $meta = [];

        if ($this->debug) {
            $meta = [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getPrevious()->getHint(),
                'trace'   => $e->getTrace(),
            ];
        }

        $json = new ErrorObject(__("passport.{$e->getPrevious()->getErrorType()}"), $e->statusCode(), $meta);

        $response->setData($json->toArray());
    }
}
