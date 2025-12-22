<?php

// TODO: Add CSRF protection?
// TODO: Use SameSite Cookies for JWT and CSRF tokens ?
// TODO: use redis for request ids and CSRF tokens storage?
// Then when a request id is used or a csrf token is used, it is removed from the redis storage
// If the request id or csrf token is not found in the redis storage, it is invalid
// TODO: add also redis for jwt validation and token storage? If the jwt token is not in the redis cache it is invalid
/*
setcookie('jwt', $token, [
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict'
]);
*/
define("ROOT_DIR", realpath(__DIR__));
$_ENV["BUNNYKITTY_ROOT_DIR"] = ROOT_DIR;

require_once ROOT_DIR . "/vendor/autoload.php";

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

use Marking\BunnyKitty\Http\ResponseWrapper;

use function Marking\BunnyKitty\Handlers\dispatchRequest;
use function Marking\BunnyKitty\Helpers\response;
use function Marking\BunnyKitty\Helpers\config;

define("DOTENV_REQUIRED", config("app.required_envs"));

try {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
    $dotenv->load();

    foreach (DOTENV_REQUIRED as $key) {
        $dotenv->required($key)->notEmpty();
    }
} catch (Exception $e) {
    die("Failed to load environment variables: " . $e->getMessage());
}

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
    $requestData = new ParameterBag($request->toArray());
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
if (
    !$requestData->has("jsonrpc") &&
    !$requestData->has("method") &&
    !$requestData->has("id")
) {
    response()->errorAndExit("Invalid JSON-RPC request");
}

if (
    $requestData->get("jsonrpc") !== "custom" &&
    $requestData->get("method") === ""
) {
    response()->errorAndExit("Invalid JSON-RPC request");
}

if ($requestData->get("id") === "" || $requestData->get("id") === null) {
    response()->errorAndExit("Invalid JSON-RPC request: id is required");
}

// print_r($requestData->get("id"));

try {
    $resultOrResponse = dispatchRequest($request, $requestData);
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

if ($resultOrResponse instanceof ResponseWrapper) {
    $resultOrResponse->sendAndExit();
}

if ($resultOrResponse instanceof Response) {
    $resultOrResponse->send();
    exit();
}

// Send response
response()->setId($requestData->get("id"))->successAndExit($resultOrResponse);
