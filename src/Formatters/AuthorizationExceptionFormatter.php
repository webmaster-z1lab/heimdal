<?php


namespace Optimus\Heimdal\Formatters;


use Exception;
use Illuminate\Http\JsonResponse;
use Optimus\Heimdal\ErrorObject;
use Illuminate\Auth\AuthenticationException;

class AuthorizationExceptionFormatter extends BaseFormatter
{
    /**
     * @param  \Illuminate\Http\JsonResponse  $response
     * @param  \Exception                     $e
     * @param  array                          $reporterResponses
     */
    public function format(JsonResponse $response, Exception $e, array $reporterResponses): void
    {
        $code = $e instanceof AuthenticationException ? 401 : 403;

        $response->setStatusCode($code);

        $meta = [];

        if ($this->debug) {
            $meta = [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'trace'   => $e->getTrace(),
            ];
        }

        $json = new ErrorObject($e->getMessage(), $code, $meta);

        $response->setData($json->toArray());
    }
}
