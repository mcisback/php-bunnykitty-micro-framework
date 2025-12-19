<?php

use function Marking\BunnyKitty\Database\MongoDB\requireModel;
use function Marking\BunnyKitty\Database\MongoDB\mongo;

// require_once $_ENV["BUNNYKITTY_ROOT_DIR"] . "/vendor/autoload.php";

return function ($request, $response) {
    $posts = requireModel("posts");

    // print_r($posts);
    //
    $result = iterator_to_array(
        $posts->find(
            [],
            [
                "limit" => 100, // Add a reasonable limit
                "sort" => ["createdAt" => -1], // Sort by newest first
            ],
        ),
    );

    return $result;
};
