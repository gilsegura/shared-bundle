<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\EventSubscriber;

use SharedBundle\UI\Http\Rest\Response\OpenApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionSubscriberTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_dispatch_exception_when_request_not_accept_json(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            Request::create('/', Request::METHOD_POST),
            HttpKernelInterface::MAIN_REQUEST,
            new \InvalidArgumentException()
        );

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        self::assertSame(OpenApi::HTTP_INTERNAL_SERVER_ERROR, $event->getResponse()->getStatusCode());
    }

    public function test_must_dispatch_exception_when_request_accept_json(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            Request::create('/', Request::METHOD_POST, [], [], [], ['HTTP_ACCEPT' => 'application/json']),
            HttpKernelInterface::MAIN_REQUEST,
            new \InvalidArgumentException()
        );

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        self::assertSame(OpenApi::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }
}
