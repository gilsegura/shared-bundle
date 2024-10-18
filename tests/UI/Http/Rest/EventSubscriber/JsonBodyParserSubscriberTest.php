<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonBodyParserSubscriberTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_dispatch_exception_when_request_is_not_json(): void
    {
        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '[]');

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch(new RequestEvent(
            self::$kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        self::assertSame([], $request->request->all());
    }

    public function test_must_dispatch_exception_when_request_is_not_valid_json(): void
    {
        $request = Request::create('/', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"data":OK}');

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch(new RequestEvent(
            self::$kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        self::assertSame([], $request->request->all());
    }

    public function test_must_dispatch_exception_when_request_is_valid_empty_json(): void
    {
        $request = Request::create('/', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => 'application/json']);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch(new RequestEvent(
            self::$kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        self::assertSame([], $request->request->all());
    }

    public function test_must_dispatch_exception_when_request_is_valid_not_empty_json(): void
    {
        $request = Request::create('/', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"data":"OK"}');

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch(new RequestEvent(
            self::$kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ), KernelEvents::REQUEST);

        self::assertSame(['data' => 'OK'], $request->request->all());
    }
}
