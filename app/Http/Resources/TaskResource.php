<?php

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array<string, mixed> */
final class TaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $status = TaskStatus::tryFrom((string) ($this->resource['status'] ?? ''))
            ?? TaskStatus::Pending;

        return [
            'id' => $this->resource['id'] ?? null,
            'title' => $this->resource['title'] ?? null,
            'description' => $this->resource['description'] ?? null,
            'status' => $status->value,
            'status_label' => $status->label(),
            'due_date' => $this->resource['due_date'] ?? null,
            'created_at' => $this->resource['created_at'] ?? null,
            'updated_at' => $this->resource['updated_at'] ?? null,
        ];
    }
}
