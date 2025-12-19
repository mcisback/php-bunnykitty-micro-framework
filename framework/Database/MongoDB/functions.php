<?php
namespace Marking\BunnyKitty\Database\MongoDB;

function mongo(?string $mongoUri = null)
{
    static $client = null;
    if ($client === null) {
        $mongoUri = $mongoUri ?? $_ENV["MONGODB_URI"];
        $uri =
            $mongoUri ??
            throw new RuntimeException(
                "Set the MONGODB_URI environment variable to your Atlas URI",
            );
        $client = new \MongoDB\Client($uri);
    }
    return $client;
}

function db(string $dbName = null)
{
    $dbName = $dbName ?? ($_ENV["MONGODB_DBNAME"] ?? null);

    if (!$dbName) {
        throw new RuntimeException("Database name not provided");
    }

    // print_r(["dbName" => $dbName]);

    return mongo()->$dbName;
}

function requireModel(string $collectionName)
{
    return db()->$collectionName;
}
