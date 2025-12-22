<?php

use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use function Marking\BunnyKitty\Helpers\config;

// TODO: update using origins white list logic
// Are this important ????
// X-Forwarded-Host
// X-Forwarded-Proto
// X-Forwarded-For
return function (Request $request, $response = null, $next = null): ?array {
    // $allowedOrigins = config("cors.allowed_origins");
    // $origin = $request->headers->get("Origin"); or Referer or X-Whatever
    // if ($origin === null) {
    // optional fallback for older clients
    //     $referer = $request->headers->get('Referer');
    //     if ($referer) {
    //         $parsed = parse_url($referer);
    //         if (isset($parsed['scheme'], $parsed['host'])) {
    //             $origin = $parsed['scheme'] . '://' . $parsed['host'];
    //         }
    //     }
    // }

    // CORS headers you want to allow
    $headers = [
        "Access-Control-Allow-Origin" => config("cors.allowed_origins"),
        "Access-Control-Allow-Methods" => "POST, OPTIONS",
        "Access-Control-Allow-Headers" => "*",
        "Access-Control-Allow-Credentials" => "true",
        "Access-Control-Max-Age" => "3600",
    ];

    // Allow only whitelisted origins
    // if ($origin && in_array($origin, $allowedOrigins, true)) {
    //     $headers["Access-Control-Allow-Origin"] = $origin;
    //     $headers["Vary"] = "Origin";
    // }

    // When receiving an OPTION request return the CORS headers and exit
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
