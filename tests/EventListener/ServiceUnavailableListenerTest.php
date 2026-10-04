<?php

namespace App\Tests\EventListener;

use App\EventListener\ServiceUnavailableListener;
use App\Exception\ProductApiException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class ServiceUnavailableListenerTest extends TestCase
{
    public function testApiErrorShowsTheUnavailablePage(): void
    {
        $event = $this->dispatch(new ProductApiException());

        self::assertSame(503, $event->getResponse()?->getStatusCode());
    }

    public function testOtherErrorsAreLeftToSymfony(): void
    {
        $event = $this->dispatch(new LogicException('bug'));

        self::assertNull($event->getResponse());
    }

    private function dispatch(Throwable $error): ExceptionEvent
    {
        $twig = new Environment(new ArrayLoader(['product/unavailable.html.twig' => '{{ message }}']));
        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $error);

        (new ServiceUnavailableListener($twig))($event);

        return $event;
    }
}
