<?php

use function Marking\BunnyKitty\Database\MongoDB\requireModel;
use function Marking\BunnyKitty\Database\MongoDB\normalizeCollection;

return function ($request, $response) {
    $posts = requireModel("posts");

    // print_r($posts);
    //
    $result = $posts->find(
        [],
        [
            "limit" => 100, // Add a reasonable limit
            "sort" => ["createdAt" => -1], // Sort by newest first
        ],
    );

    return $result->toArray();
};
