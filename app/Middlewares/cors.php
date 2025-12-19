<?php

use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// NOTE: add a STOP_REQUEST and CONTINUE_REQUEST enums instead of null ?
// TODO: Add generic middleware ?
return function (Request $request, $response = null, $next = null): ?array {
    // CORS headers you want to allow
    $headers = [
        "Access-Control-Allow-Origin" => "http://localhost:3000",
        "Access-Control-Allow-Methods" => "POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type, Authorization",
        "Access-Control-Allow-Credentials" => "true",
        "Access-Control-Max-Age" => "3600",
    ];

    // Handle preflight requests
    if ($request->getMethod() === Request::METHOD_OPTIONS) {
        $response = new JsonResponse("", JsonResponse::HTTP_NO_CONTENT);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        $response->send();
        exit();
    }

    if ($next !== null) {
        return $next($request, $response);
    }

    return [$request, $response];
};
