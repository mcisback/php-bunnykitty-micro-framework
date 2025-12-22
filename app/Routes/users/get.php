<?php

return function ($request, $response) {
    return [
        [
            "id" => 1,
            "name" => "Luca Bianchi",
            "email" => "luca.bianchi@example.com",
            "age" => 29,
            "role" => "admin",
            "active" => true,
        ],
        [
            "id" => 2,
            "name" => "Sara Rossi",
            "email" => "sara.rossi@example.com",
            "age" => 34,
            "role" => "editor",
            "active" => true,
        ],
        [
            "id" => 3,
            "name" => "Marco De Santis",
            "email" => "marco.desantis@example.com",
            "age" => 41,
            "role" => "user",
            "active" => false,
        ],
        [
            "id" => 4,
            "name" => "Giulia Conti",
            "email" => "giulia.conti@example.com",
            "age" => 26,
            "role" => "user",
            "active" => true,
        ],
        [
            "id" => 5,
            "name" => "Paolo Ferri",
            "email" => "paolo.ferri@example.com",
            "age" => 38,
            "role" => "moderator",
            "active" => false,
        ],
    ];
};
