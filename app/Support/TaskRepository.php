<?php

namespace App\Support;

use App\Exceptions\TaskStoreException;
use Illuminate\Support\Str;

final class TaskRepository
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public function __construct(private readonly string $path)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->withLock(LOCK_SH, fn (): array => $this->read());
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $task) {
            if (($task['id'] ?? null) === $id) {
                return $task;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array
    {
        return $this->withLock(LOCK_EX, function () use ($attributes): array {
            $tasks = $this->read();
            $now = now()->toIso8601String();

            $task = [
                'id' => (string) Str::uuid(),
                'title' => (string) $attributes['title'],
                'description' => $attributes['description'] ?? null,
                'status' => $attributes['status'] ?? 'pending',
                'due_date' => $attributes['due_date'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $tasks[] = $task;
            $this->write($tasks);

            return $task;
        });
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>|null
     */
    public function update(string $id, array $attributes): ?array
    {
        return $this->withLock(LOCK_EX, function () use ($id, $attributes): ?array {
            $tasks = $this->read();

            foreach ($tasks as $index => $task) {
                if (($task['id'] ?? null) !== $id) {
                    continue;
                }

                $updated = array_merge($task, $attributes, [
                    'updated_at' => now()->toIso8601String(),
                ]);

                $tasks[$index] = $updated;
                $this->write($tasks);

                return $updated;
            }

            return null;
        });
    }

    public function delete(string $id): bool
    {
        return $this->withLock(LOCK_EX, function () use ($id): bool {
            $tasks = $this->read();
            $found = false;

            foreach ($tasks as $index => $task) {
                if (($task['id'] ?? null) === $id) {
                    unset($tasks[$index]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $this->write(array_values($tasks));
            }

            return $found;
        });
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withLock(int $mode, callable $callback): mixed
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw TaskStoreException::unwritable($directory);
        }

        $lock = fopen($this->path.'.lock', 'c');

        if ($lock === false) {
            throw TaskStoreException::unwritable($this->path.'.lock');
        }

        try {
            if (!flock($lock, $mode)) {
                throw TaskStoreException::unwritable($this->path);
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return list<array<string, mixed>> */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $raw = file_get_contents($this->path);

        if ($raw === false) {
            throw TaskStoreException::unreadable($this->path);
        }

        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw TaskStoreException::unreadable($this->path, 'JSON tidak valid');
        }

        return $decoded;
    }

    /** @param list<array<string, mixed>> $tasks */
    private function write(array $tasks): void
    {
        $json = json_encode($tasks, self::JSON_FLAGS);

        if ($json === false) {
            throw TaskStoreException::unwritable($this->path);
        }

        $temporary = $this->path.'.tmp';

        if (file_put_contents($temporary, $json.PHP_EOL) === false) {
            throw TaskStoreException::unwritable($this->path);
        }

        if (!rename($temporary, $this->path)) {
            @unlink($temporary);

            throw TaskStoreException::unwritable($this->path);
        }
    }
}
