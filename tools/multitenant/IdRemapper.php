<?php

final class IdRemapper
{
    /** @var array<int, int> */
    private array $map = [];
    private int $nextId;

    public function __construct(int $startId)
    {
        $this->nextId = $startId;
    }

    public function remapId(int $oldId): int
    {
        if (!isset($this->map[$oldId])) {
            $this->map[$oldId] = $this->nextId;
            $this->nextId++;
        }

        return $this->map[$oldId];
    }

    public function hasMapping(int $oldId): bool
    {
        return isset($this->map[$oldId]);
    }

    public function getMapping(int $oldId): ?int
    {
        return $this->map[$oldId] ?? null;
    }

    public function count(): int
    {
        return count($this->map);
    }
}
