<?php

namespace App\EventListener;

use App\Exception\ProductApiException;
use Doctrine\DBAL\Exception as DbalException;
use PDOException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

#[AsEventListener(priority: -10)]
final class ServiceUnavailableListener
{
    public function __construct(private Environment $twig)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $error = $event->getThrowable();

        $message = match (true) {
            $error instanceof ProductApiException => $error->getMessage(),
            $error instanceof PDOException, $error instanceof DbalException => 'Les commentaires sont momentanément indisponibles. Veuillez réessayer dans quelques instants.',
            default => null,
        };

        if (null !== $message) {
            $html = $this->twig->render('product/unavailable.html.twig', ['message' => $message]);
            $event->setResponse(new Response($html, Response::HTTP_SERVICE_UNAVAILABLE));
        }
    }
}
