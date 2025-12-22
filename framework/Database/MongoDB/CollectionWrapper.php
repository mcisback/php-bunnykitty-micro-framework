<?php

namespace Marking\BunnyKitty\Database\MongoDB;

class CollectionWrapper
{
    protected \MongoDB\Collection $collection;
    protected ?\MongoDB\Driver\Cursor $cursor = null;

    public function __construct(\MongoDB\Collection $collection)
    {
        $this->setCollection($collection);
    }

    public function setCollection(\MongoDB\Collection $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    public function find(
        array $query = [],
        array $options = [],
    ): CollectionWrapper {
        $this->cursor = $this->collection->find($query, $options);

        return $this;
    }

    public function findOne(array $query = [], array $options = [])
    {
        return $this->collection->findOne($query, $options);
    }

    public function insertOne(array $document): array
    {
        return $this->collection->insertOne($document);

        // return $this->collection->findOne(["_id" => $document["_id"]]);
    }

    public function updateOne(array $filter, array $update): array
    {
        return $this->collection->updateOne($filter, $update);

        // return $this->collection->findOne($filter);
    }

    public function deleteOne(array $filter): array
    {
        return $this->collection->deleteOne($filter);

        // return $this->collection->findOne($filter);
    }

    public function getCollection(): \MongoDB\Collection
    {
        return $this->collection;
    }

    public function toArray(): array
    {
        if ($this->cursor === null) {
            throw new \Exception("CollectionWrapper::cursor is null");
        }

        // if ($this->cursor->count() === 0) {
        //     return [];
        // }

        return array_map(function ($doc) {
            $doc["id"] = (string) $doc["_id"];
            unset($doc["_id"]);
            return $doc;
        }, iterator_to_array($this->cursor));
    }
}
