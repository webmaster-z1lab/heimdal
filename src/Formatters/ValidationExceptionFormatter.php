<?php


namespace Optimus\Heimdal\Formatters;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Optimus\Heimdal\ErrorObject;

class ValidationExceptionFormatter extends BaseFormatter
{
    /**
     * @param  \Illuminate\Http\JsonResponse  $response
     * @param  \Exception                     $e
     * @param  array                          $reporterResponses
     */
    public function format(JsonResponse $response, Exception $e, array $reporterResponses): void
    {
        if ($e instanceof ValidationException) {
            $errors = $e->errors();
            $error = new ErrorObject($errors, 422, $errors);
            $response->setStatusCode(422);
            $response->setData($error->toArray());
        }
    }
}
