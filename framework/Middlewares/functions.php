<?php

namespace Marking\BunnyKitty\Middlewares;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use function Marking\BunnyKitty\Helpers\response;

function createMiddleware(
    callable $middleware,
    Request $request,
    $response = null,
    $next = null,
): \Closure {
    return function ($request, $response = null, $next = null) use (
        $middleware,
    ) {
        if ($response !== null && !($response instanceof JsonResponse)) {
            response()->errorAndExit(
                "Response is not a JsonResponse",
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return $middleware($request, $response, $next);
    };
}
