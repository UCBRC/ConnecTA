<?php
/**
 * Created by PhpStorm.
 * User: huqin
 * Date: 2018/9/4
 * Time: 8:25
 */

namespace App;


use Symfony\Component\HttpFoundation\Request;

class Library
{
    static function checkCsrf(Request $request) {
        return
            $request->headers->has("client") ||
            $_SERVER['APP_ENV'] === "dev" ||
            ($request->headers->has("referer") && parse_url($request->headers->get("referer"))["host"] === "connecta.betterrelations.org") ||
            $request->getPathInfo() == "/oauth/authorize";

    }
}