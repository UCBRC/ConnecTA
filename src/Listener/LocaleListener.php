<?php
namespace App\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleListener {
    public function onKernelRequest(RequestEvent $event)
    {
        // Set the locale
        $request = $event->getRequest();
        if($request->cookies->get("lang") === "zh")
            $request->setLocale("zh");
        else if ($request->cookies->get("lang") === "en")
            $request->setLocale("en");
        else if(strpos($request->headers->get("Accept-Language"),"zh") === false)
            $request->setLocale("en");
        else
            $request->setLocale("zh");
        $request->setLocale("zh");

    }
}