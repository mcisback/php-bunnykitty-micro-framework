<?php

namespace Marking\BunnyKitty\Handlers;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Marking\BunnyKitty\Http\ResponseWrapper;
use function Marking\BunnyKitty\Helpers\config;
use function Marking\BunnyKitty\Helpers\pathFromRootDir;
use function Marking\BunnyKitty\Helpers\response;
use function Marking\BunnyKitty\Middlewares\createMiddleware;

use function Symfony\Component\String\u;

// NOTE: maybe better to throw errors instead of responses and catch them in index.php
function dispatchRequest(Request $request, ParameterBag|array $requestData)
{
    $requestData = is_array($requestData)
        ? new ParameterBag($requestData)
        : $requestData;

    $handler = u($requestData->get("method"));
    $params = $requestData->get("params") ?? [];
    $id = $requestData->get("id");

    if ($handler->containsAny("..")) {
        response()->errorAndExit(
            "Invalid method ..",
            Response::HTTP_BAD_REQUEST,
        );
    }

    $handlerPath = pathFromRootDir(
        "app",
        "Routes",
        $handler->toString() . ".php",
    );

    // Check for directory traversal
    if (!$handlerPath->startsWith($_ENV["BUNNYKITTY_ROOT_DIR"])) {
        response()->errorAndExit(
            "Invalid method path: $handlerPath",
            Response::HTTP_BAD_REQUEST,
        );
    }

    if (!file_exists($handlerPath)) {
        response()->errorAndExit("Handler not found", Response::HTTP_NOT_FOUND);
    }

    $middlewares = config("routes.config.{$handler->toString()}.middlewares");

    // print_r($middlewares);

    $middlewareResponse = null;

    foreach ($middlewares as $md) {
        $middlewarePath = pathFromRootDir("app", "Middlewares", $md . ".php");

        if (!file_exists($middlewarePath)) {
            continue;

            // NOTE: maybe better to log instead of throwing an error
            response()->errorAndExit(
                "Middleware not found",
                Response::HTTP_NOT_FOUND,
            );
        }

        $middleware = require_once $middlewarePath;

        if (!is_callable($middleware)) {
            continue;

            // NOTE: maybe better to log instead of throwing an error
            response()->errorAndExit(
                "Middleware is not callable",
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        $middleware = createMiddleware(
            $middleware,
            $request,
            $middlewareResponse,
        );

        [$middlewareRequest, $middlewareResponse] = $middleware(
            $request,
            $middlewareResponse,
        );

        $request = $middlewareRequest;
    }

    $handler = require_once $handlerPath->toString();

    // print_r([
    //     "handlerPath" => $handlerPath->toString(),
    //     "handler" => $handler,
    //     "isCallable" => is_callable($handler),
    // ]);

    if (!is_callable($handler)) {
        response()->errorAndExit(
            "Handler is not callable",
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    if ($middlewareResponse === null) {
        $middlewareResponse = new ResponseWrapper();
    }

    if (
        $middlewareResponse instanceof ResponseWrapper &&
        $middlewareResponse->getId() === null
    ) {
        $middlewareResponse->setId($id);
    }

    $resultOrResponse = $handler($request, $middlewareResponse, ...$params);

    return [$resultOrResponse];
}
