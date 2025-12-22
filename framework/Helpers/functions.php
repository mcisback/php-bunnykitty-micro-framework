<?php

namespace Marking\BunnyKitty\Helpers;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\JsonResponse;
use function Symfony\Component\String\u;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Marking\BunnyKitty\Http\ResponseWrapper;
use Marking\BunnyKitty\Config\Manager as ConfigManager;

function config(string $key = "")
{
    if (!isset($key) || empty($key)) {
        return ConfigManager::getInstance();
    }

    return ConfigManager::getInstance()->get($key);
}

function pathFromRootDir(string ...$paths)
{
    return u(realpath(Path::join($_ENV["BUNNYKITTY_ROOT_DIR"], ...$paths)));
}

function response(?JsonResponse $response = null): ResponseWrapper
{
    return new ResponseWrapper($response);
}

function verifyJwt(string $token)
{
    $secret = $_ENV["JWT_SECRET"];

    try {
        $decoded = JWT::decode($token, new Key($secret, "HS256"));

        if (
            $decoded->iss !==
                ($_ENV["JWT_ISSUER"] ?? "http://localhost:8888") ||
            $decoded->aud !== ($_ENV["JWT_AUDIENCE"] ?? "http://localhost:8888")
        ) {
            throw new Exception("Invalid token issuer or audience");
        }

        return $decoded;
    } catch (\Firebase\JWT\ExpiredException $e) {
        response()->unauthorized("Auth token expired");
    } catch (\Exception $e) {
        response()->unauthorized("Invalid auth token: {$e->getMessage()}");
    }
}

// iss (Issuer): Who created the token → "http://localhost:8888"
// aud (Audience): Who should accept the token → "http://localhost:8888"
// sub (Subject): Who the token is about → $user->id (the authenticated user)
// iat (Issued At): When was it created → timestamp
// exp (Expiration): When does it expire → timestamp

function createJWT($user)
{
    $secret = $_ENV["JWT_SECRET"];
    $now = time();
    $maxAge = 60 * 60 * 48; // 48 hours

    $payload = [
        "iss" => $_ENV["JWT_ISSUER"] ?? "http://localhost:8888", // Issued at
        "aud" => $_ENV["JWT_AUDIENCE"] ?? "http://localhost:8888", // Audience
        "sub" => $user->id,
        "user" => $user,
        "iat" => $now,
        "exp" => $now + $maxAge,
    ];

    return JWT::encode($payload, $secret, "HS256");
}
