<?php

// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use function Marking\BunnyKitty\Helpers\response;
use function Marking\BunnyKitty\Helpers\verifyJwt;

// TODO: Use SameSite cookies to store token ?
return function (Request $request, $response = null, $next = null): ?array {
    $authHeader = $request->headers->get("Authorization");

    if ($authHeader && str_starts_with($authHeader, "Bearer ")) {
        $authToken = substr($authHeader, 7);
    }

    $jwtDecoded = verifyJwt($authToken);

    $request->attributes->set("user", $jwtDecoded->user);
    $request->attributes->set("authorized", true);

    if ($next !== null) {
        return $next($request, $response);
    }

    return [$request, $response];
};
