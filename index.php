<?php

require_once realpath(__DIR__) . "/vendor/autoload.php";

Dotenv\Dotenv::createImmutable(realpath(__DIR__))->load();

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

use function Marking\BunnyKitty\Handlers\dispatchRequest;
use function Marking\BunnyKitty\Helpers\response;

$_ENV["BUNNYKITTY_ROOT_DIR"] = realpath(__DIR__);

// Create a Request object from PHP globals
$request = Request::createFromGlobals();

if (!$request->isMethod("POST")) {
    if (!$request->isMethod("OPTIONS")) {
        response()->errorAndExit(
            "Method not allowed. Only POST and OPTIONS requests are accepted.",
            Response::HTTP_METHOD_NOT_ALLOWED,
        );
    }
}

$contentType = $request->headers->get("Content-Type");

if ($contentType !== "application/json") {
    response()->errorAndExit("Invalid Content-Type");
}

// Method 1: Get JSON content as string and decode manually
$content = $request->getContent();
$data = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    response()->errorAndExit("Invalid JSON: " . json_last_error_msg());
}

// Method 2: Use toArray() method (Symfony 6.3+)
// This automatically decodes JSON and throws exception if invalid
try {
    $data = new ParameterBag($request->toArray());
} catch (\JsonException $e) {
    response()->errorAndExit("Invalid JSON: " . $e->getMessage());
}

/*
id exists so a client can match a response to a request.

It becomes essential when:

You send multiple requests without waiting for each response.

You use batch requests.

You use async or concurrent calls over the same connection.
*/

// Access JSON data
if (!$data->has("jsonrpc") && !$data->has("method") && !$data->has("id")) {
    response()->errorAndExit("Invalid JSON-RPC request");
}

if (
    $data->get("jsonrpc") !== "custom" &&
    $data->get("method") === "" &&
    $data->get("id") === "" &&
    $data->get("id") === null
) {
    response()->errorAndExit("Invalid JSON-RPC request");
}

try {
    $result = dispatchRequest($request, $data);
    // } catch (\TypeError $e) {
    //     response()->errorAndExit(
    //         "Handler params type mismatch: " . $e->getMessage(),
    //     );
    // } catch (\ArgumentCountError $e) {
    //     response()->errorAndExit(
    //         "Handler parameters count mismatch: " . $e->getMessage(),
    //     );
    // } catch (\Throwable $e) {
    //     response()->errorAndExit(
    //         "Handler unknown throwable: " . $e->getMessage(),
    //     );
} catch (\Exception $e) {
    response()->errorAndExit("Handler unknown exception: " . $e->getMessage());
}

// Send response
response()->successAndExit($result);
