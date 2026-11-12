<?php

namespace App\Providers;

use App\Support\TaskRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TaskRepository::class,
            static fn (): TaskRepository => new TaskRepository(
                (string) config('tasks.path')
            )
        );
    }

    public function boot(): void
    {
        //
    }
}
