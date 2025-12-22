<?php
namespace Marking\BunnyKitty\Database\MongoDB;

use Marking\BunnyKitty\Database\MongoDB\CollectionWrapper;

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

// function normalizeCollection(iterable $cursor): array
// {
//     return array_map(function ($doc) {
//         $doc["id"] = (string) $doc["_id"];
//         unset($doc["_id"]);
//         return $doc;
//     }, iterator_to_array($cursor));
// }

function requireModel(string $collectionName)
{
    $parts = explode(".", $collectionName);
    $collection = null;

    if (count($parts) > 1) {
        $dbName = $parts[0];
        $collectionName = $parts[1];

        $collection = db($dbName)->$collectionName;
    }

    $collection = $collection ?? db()->$collectionName;

    return new CollectionWrapper($collection);
}
