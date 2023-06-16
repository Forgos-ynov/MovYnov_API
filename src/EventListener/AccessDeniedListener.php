<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessDeniedListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {
            $response = new JsonResponse(['message' => "Vous n'êtes pas authorisés à accéder à cette ressource"], 403);
            $event->setResponse($response);
        }
    }
}
