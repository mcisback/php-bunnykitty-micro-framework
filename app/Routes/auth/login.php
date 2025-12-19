<?php

return function ($request, $response, $username, $password) {
    if ($username === "admin" && $password === "password") {
        return [
            "token" => "my-custom-token",
            "user_id" => 1,
            "auth" => true,
            "message" => "Login successful",
        ];
    }

    return [
        "message" => "Invalid credentials",
        "auth" => false,
    ];
};
