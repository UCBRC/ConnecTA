<?php
/**
 * Created by PhpStorm.
 * User: SYSTEM
 * Date: 2018/8/7
 * Time: 0:50
 */

namespace App\Listener;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $filterResponseEvent) {
        $response = $filterResponseEvent->getResponse();
        $request = $filterResponseEvent->getRequest();
        $client = $request->headers->get("Client");
        if($client === "weChat" || $client === "iOS" || $client === "Android") {
            if($response instanceof RedirectResponse) {
                $filterResponseEvent->setResponse(JsonResponse::create(["data"=>$response->getTargetUrl(), "code"=>Response::HTTP_TEMPORARY_REDIRECT]));
            } else if($response->getStatusCode() === 302) {
                $filterResponseEvent->setResponse(JsonResponse::create(["data"=>$response->headers->get("Location"), "code"=>Response::HTTP_TEMPORARY_REDIRECT]));
            }
        }
    }
}