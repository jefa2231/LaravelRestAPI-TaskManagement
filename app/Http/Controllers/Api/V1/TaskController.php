<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Support\TaskRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskController extends Controller
{
    public function __construct(private readonly TaskRepository $tasks)
    {
    }

    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $keyword = trim((string) ($validated['q'] ?? ''));

        $tasks = collect($this->tasks->all())
            ->when(
                isset($validated['status']),
                fn ($collection) => $collection->where('status', $validated['status'])
            )
            ->when(
                $keyword !== '',
                fn ($collection) => $collection->filter(
                    fn (array $task): bool => $this->matches($task, $keyword)
                )
            )
            ->sortByDesc('created_at')
            ->values();

        $paginator = new LengthAwarePaginator(
            $tasks->forPage($page, $perPage)->values(),
            $tasks->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return TaskResource::collection($paginator);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($this->normalize($request->validated()));

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tasks.show', $task['id']));
    }

    public function show(string $id): TaskResource
    {
        $task = $this->tasks->find($id);

        abort_if($task === null, 404, 'Tugas tidak ditemukan.');

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, string $id): TaskResource
    {
        $task = $this->tasks->update($id, $this->normalize($request->validated()));

        abort_if($task === null, 404, 'Tugas tidak ditemukan.');

        return new TaskResource($task);
    }

    public function destroy(string $id): Response
    {
        abort_unless($this->tasks->delete($id), 404, 'Tugas tidak ditemukan.');

        return response()->noContent();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('due_date', $data)) {
            $data['due_date'] = $data['due_date'] === null
                ? null
                : Carbon::parse($data['due_date'])->toIso8601String();
        }

        return $data;
    }

    /** @param array<string, mixed> $task */
    private function matches(array $task, string $keyword): bool
    {
        foreach (['title', 'description'] as $field) {
            $value = (string) ($task[$field] ?? '');

            if ($value !== '' && mb_stripos($value, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
