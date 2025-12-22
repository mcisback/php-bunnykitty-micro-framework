<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use function Marking\BunnyKitty\Database\MongoDB\requireModel;
use function Marking\BunnyKitty\Database\MongoDB\mongo;
use function Marking\BunnyKitty\Helpers\response;
use function Marking\BunnyKitty\Helpers\createJWT;

return function ($request, $response, $usernameOrEmail, $password) {
    $users = requireModel("users");

    $user = $users->findOne([
        "username" => $usernameOrEmail,
    ]);

    if (!$user) {
        $user = $users->findOne([
            "email" => $usernameOrEmail,
        ]);
    }

    if (!$user) {
        response()->unauthorized("User not found");
    }

    if (!password_verify($password, $user->password)) {
        response()->unauthorized("Invalid credentials");
    }

    unset($user["password"]);

    $user["id"] = (string) $user->_id;
    unset($user["_id"]);

    $token = createJWT($user);

    return [
        "token" => $token,
        "user" => $user,
        "message" => "Login successful",
    ];
};
