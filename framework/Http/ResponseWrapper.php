<?php

namespace Marking\BunnyKitty\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

// TODO: Extends Symfony\Component\HttpFoundation\Response or JsonResponse
class ResponseWrapper
{
    protected JsonResponse $response;

    public function __construct(?Response $response)
    {
        $this->response = $response ?? new JsonResponse();
    }

    public function create(
        ParameterBag|array $json,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $data = is_array($json) ? $json : $json->all();
        $id = $data["id"] ?? null;

        unset($data["id"]);

        $this->response->setData([
            "jsonrpc" => "custom",
            ...$data,
        ]);
        $this->response->setStatusCode($status);

        return $this->response;
    }

    public function sendJsonAndExit(
        ParameterBag|array $json = [],
        int $status = Response::HTTP_OK, // Use constant instead of magic number
    ): void {
        $this->create($json, $status)->send();

        exit();
    }

    public function successAndExit(
        ParameterBag|array $data = [],
        string $message = "Success",
    ): void {
        $responseData = [
            "success" => true,
            "message" => isset($data["message"]) ? $data["message"] : $message,
            "result" => is_array($data) ? $data : $data->all(),
        ];

        $this->sendJsonAndExit($responseData, Response::HTTP_OK);
    }

    public function errorAndExit(
        string $message = "Error",
        int $status = Response::HTTP_BAD_REQUEST,
    ): void {
        $responseData = [
            "success" => false,
            "error" => [
                "code" => $status,
                "message" => $message,
            ],
        ];

        $this->sendJsonAndExit($responseData, $status);
    }

    public function notFoundAndExit(
        string $message = "Resource not found",
    ): void {
        $this->errorAndExit($message, Response::HTTP_NOT_FOUND);
    }

    public function unauthorized(string $message = "Unauthorized"): void
    {
        $this->errorAndExit($message, Response::HTTP_UNAUTHORIZED);
    }
}
