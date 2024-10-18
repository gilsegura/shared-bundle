<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\EventSubscriber;

use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;
use SharedBundle\UI\Http\Rest\Exception\ExceptionMessageTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    use ExceptionMessageTrait;

    public function __construct(
        private bool $debug,
        private ExceptionHttpStatusCodeMapping $exceptionHttpStatusCodeMapping,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isJsonAcceptable($request)) {
            return;
        }

        $exception = $event->getThrowable();

        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/vnd.api+json');

        $statusCode = $this->exceptionHttpStatusCodeMapping->handle($exception);

        $response->setStatusCode($statusCode);
        $response->setData($this->errorMessage($exception));

        $event->setResponse($response);
    }

    private function isJsonAcceptable(Request $request): bool
    {
        return \in_array('application/json', $request->getAcceptableContentTypes())
            || \in_array('application/vnd.api+json', $request->getAcceptableContentTypes());
    }

    private function errorMessage(\Throwable $exception): array
    {
        $error = $this->error($exception);

        if (!$this->debug) {
            return $error;
        }

        return [...$error, ...$this->metadata($exception)];
    }
}
