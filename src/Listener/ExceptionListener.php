<?php
namespace App\Listener;

use DeviceDetector\DeviceDetector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        if($_SERVER['APP_DEBUG'] ?? ('prod' !== ($_SERVER['APP_ENV'] ?? 'dev')))
            return;
        $exception = $event->getThrowable();
        if($exception instanceof HttpExceptionInterface){
            if($exception instanceof NotFoundHttpException) {
                $response = new RedirectResponse("/");
            } else {
                $response = new JsonResponse(array(
                    "code" => $exception->getStatusCode(),
                    "data" => $exception->getMessage()
                ),$exception->getStatusCode());
            }
            $event->setResponse($response);
        }
    }
}