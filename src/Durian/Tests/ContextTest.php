<?php

namespace Durian\Tests;

use Durian\Context;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ContextTest.
 *
 * @author Chris Heng <bigblah@gmail.com>
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $context = new Context();

        $this->assertNull($context->request());
        $this->assertNull($context->response());
        $this->assertFalse($context->master());
        $this->assertNull($context->params('foo'));
        $this->assertNull($context->last());
    }

    public function testRequest()
    {
        $context = new Context();
        $request1 = new Request();
        $context->request($request1);

        $this->assertSame($request1, $context->request());

        $request2 = new Request();
        $context->request($request2);

        $this->assertSame($request2, $context->request());
    }

    public function testResponse()
    {
        $context = new Context();
        $response = new Response('foo');
        $context->response($response);

        $this->assertSame($response, $context->response());
        $this->assertSame('foo', $context->response()->getContent());

        $context->response('I\'m a teapot', 418, ['X-Temperature-Celsius' => 50]);

        $this->assertSame('I\'m a teapot', $context->response()->getContent());
        $this->assertSame(418, $context->response()->getStatusCode());
        $this->assertSame(50, $context->response()->headers->get('X-Temperature-Celsius'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testError()
    {
        $context = new Context();

        $context->error();
    }

    public function testErrorWithException()
    {
        $context = new Context();

        try {
            $context->error(new \RuntimeException('foo'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertSame('foo', $e->getMessage());
        }
    }

    public function testErrorWithFullArguments()
    {
        $context = new Context();

        try {
            $context->error('bar', 403, ['foo' => 'bar'], 42);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony\\Component\\HttpKernel\\Exception\\HttpException', $e);
            $this->assertSame('bar', $e->getMessage());
            $this->assertSame(['foo' => 'bar'], $e->getHeaders());
            $this->assertSame(42, $e->getCode());
        }
    }

    public function testMaster()
    {
        $context = new Context();
        $context->request(new Request(), HttpKernelInterface::MASTER_REQUEST);

        $this->assertTrue($context->master());

        $context->request(new Request(), HttpKernelInterface::SUB_REQUEST);

        $this->assertFalse($context->master());
    }

    public function testParams()
    {
        $context = new Context();
        $context->params(['foo' => 'bar', 'bar' => 'foo']);

        $this->assertSame('bar', $context->params('foo'));
        $this->assertSame('foo', $context->params('bar'));
        $this->assertNull($context->params('baz'));
        $this->assertSame('foo', $context->params('baz', 'foo'));

        $context->params(['foo' => 'baz', 'bar' => null]);

        $this->assertSame('baz', $context->params('foo'));
        $this->assertNull($context->params('bar'));
        $this->assertNull($context->params('bar', 'baz'));

        $this->assertSame(['foo' => 'baz', 'bar' => null], $context->params());
    }

    public function testLast()
    {
        $context = new Context();
        $context->last('foo');

        $this->assertSame('foo', $context->last());

        $context->last('bar');

        $this->assertSame('bar', $context->last());
    }

    public function testClear()
    {
        $context = new Context();

        $context->request(new Request(), HttpKernelInterface::MASTER_REQUEST);
        $context->response(new Response('foo'));
        $context->params(['foo' => 'bar']);
        $context->last('foo');
        $context->clear();

        $this->assertNull($context->request());
        $this->assertNull($context->response());
        $this->assertFalse($context->master());
        $this->assertNull($context->params('foo'));
        $this->assertNull($context->last());
    }
}
