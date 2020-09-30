<?php

namespace Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Optimus\Heimdal\ExceptionHandler;
use Optimus\Heimdal\Formatters\BaseFormatter;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Exception;

class ExceptionFormatter extends BaseFormatter
{
    public function format(JsonResponse $response, Exception $e, array $reporterResponses)
    {
        $response->setData(['message' => 'Base']);
    }
}

class HttpExceptionFormatter extends BaseFormatter
{
    public function format(JsonResponse $response, Exception $e, array $reporterResponses)
    {
        $response->setData(['message' => 'Http']);
    }
}

class ExceptionHandlerTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        app()['config']->set('optimus.heimdal', getConfigStub());
    }

    /**
     * @return ExceptionHandler
     */
    private function createHandler()
    {
        app()->bind(TestReporter::class, function ($app) {
            return function (array $config) {
                return new TestReporter($config);
            };
        });

        return app()->make(ExceptionHandler::class);
    }

    public function testReport()
    {
        $handler = $this->createHandler();

        $handler->report(new Exception('Test'));
        $responses = $handler->getReportResponses();

        $this->assertEquals([
            'test'  => 'Test: 1234',
            'test2' => 'Test: 4321',
        ], $responses);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReportIgnoredException()
    {
        $handler = $this->createHandler();

        $exception = new Exception('Test');

        $reflectionHandler = new \ReflectionClass($handler);

        $property = $reflectionHandler->getProperty('dontReport');

        $property->setAccessible(TRUE);

        $property->setValue($handler, [
            get_class($exception),
        ]);

        $responses = $reflectionHandler->getMethod('report')
            ->invoke($handler, $exception);

        $this->assertEquals([], $responses);
    }

    public function testRendersAppropriateFormatter()
    {
        app()['config']->set('optimus.heimdal.formatters', [
            HttpException::class => HttpExceptionFormatter::class,
            Exception::class     => ExceptionFormatter::class,
        ]);

        $handler = $this->createHandler();

        $request = Request::capture();

        $response = $handler->render($request, new Exception('Test'));

        $this->assertEquals('Base', $response->getData()->message);

        $response = $handler->render($request, new NotFoundHttpException('Test'));

        $this->assertEquals('Http', $response->getData()->message);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReportInvalidReporterClass()
    {
        $handler = $this->createHandler();

        $exception = new Exception('Test');

        $reflectionHandler = new \ReflectionClass($handler);

        $property = $reflectionHandler->getProperty('config');

        $property->setAccessible(TRUE);

        $config = $property->getValue($handler);

        $config['reporters'] = [
            'invalid' => [
                'class' => \stdClass::class,
            ],
        ];

        $property->setValue($handler, $config);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'invalid: stdClass is not a valid reporter class.'
        );

        $reflectionHandler->getMethod('report')
            ->invoke($handler, $exception);
    }

    /**
     * @throws \ReflectionException
     */
    public function testInvalidFormatterClass()
    {
        $handler = $this->createHandler();

        $request = NULL;

        $exception = new Exception('Test');
        $formatter = new \stdClass();

        $reflectionHandler = new \ReflectionClass($handler);

        $property = $reflectionHandler->getProperty('config');

        $property->setAccessible(TRUE);

        $config = $property->getValue($handler);

        $config['formatters'] = [
            get_class($exception) => get_class($formatter),
        ];

        $property->setValue($handler, $config);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            sprintf(
                "% is not a valid formatter class.",
                get_class($formatter)
            )
        );

        $method = $reflectionHandler->getMethod('generateExceptionResponse');

        $method->setAccessible(TRUE);

        $method->invokeArgs($handler, [$request, $exception]);
    }
}
