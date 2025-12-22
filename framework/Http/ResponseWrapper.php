<?php

namespace Marking\BunnyKitty\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

// TODO: Extends Symfony\Component\HttpFoundation\Response or JsonResponse
class ResponseWrapper
{
    protected JsonResponse $response;
    protected ParameterBag|array $json = [];
    protected int $status = Response::HTTP_OK;
    protected ?string $id = null;

    public function __construct(?Response $response = null)
    {
        $this->response = $response ?? new JsonResponse();
    }

    public function create(
        ParameterBag|array $data,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $this->setData([
            "jsonrpc" => "custom",
            ...$data,
        ])->setStatusCode($status);

        return $this->response;
    }

    public function setId(string $id): ResponseWrapper
    {
        $this->id = $id;

        return $this;
    }

    public function setData(ParameterBag|array $data): ResponseWrapper
    {
        $data = is_array($data) ? $data : $data->all();
        unset($data["id"]);

        $this->response->setData([
            "jsonrpc" => "custom",
            "id" => $this->id,
            ...$data,
        ]);

        $this->json = $data;

        return $this;
    }

    public function setStatusCode(int $status): ResponseWrapper
    {
        $this->status = $status;
        $this->response->setStatusCode($status);

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function sendAndExit()
    {
        $this->response->send();
        exit();
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
