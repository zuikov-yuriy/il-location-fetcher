<?php

namespace TheP6\ILLocationFetcher\CKANFetcher;

use TheP6\ILLocationFetcher\Exceptions\InvalidRecordStructure;

class RecordTransformer
{
    private array $transformMap = [];

    public function setTransformMap(array $transformMap): self
    {
        $this->transformMap = $transformMap;
        return $this;
    }

    public function transform(array $record): array
    {
        $transformed = [];

        foreach ($this->transformMap as $originalKey => $mappedKey) {
            if (!key_exists($originalKey, $record)) {
                throw new InvalidRecordStructure("Key {$originalKey} mapped to {$mappedKey} does not exists!");
            }

            $transformed[$mappedKey] = is_string($record[$originalKey]) ? trim($record[$originalKey]) : $record[$originalKey];
        }

        return $transformed;
    }
}