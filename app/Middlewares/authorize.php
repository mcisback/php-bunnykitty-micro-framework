<?php

// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use function Marking\BunnyKitty\Helpers\response;

// NOTE: add a STOP_REQUEST and CONTINUE_REQUEST enums instead of null ?
// TODO: Add generic middleware ?
return function (Request $request, $response = null, $next = null): ?array {
    $authHeader = $request->headers->get("Authorization");

    if ($authHeader && str_starts_with($authHeader, "Bearer ")) {
        $authToken = substr($authHeader, 7);
    }

    if ($authToken !== "my-custom-token") {
        // echo "Authorized with token: $authToken";

        // exit();
        //
        response()->unauthorized("Missing or invalid token");
    }

    $request->attributes->set("authToken", $authToken);
    $request->attributes->set("authorized", true);

    if ($next !== null) {
        return $next($request, $response);
    }

    return [$request, $response];
};
