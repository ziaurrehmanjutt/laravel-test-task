<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ExecuteScheduledTasks extends Command
{
    protected $signature = 'tasks:execute';

    protected $description = 'Execute tasks that are scheduled to run.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get all tasks that need to be executed (scheduled in the past and are not completed or failed)
        $tasks = Task::where('schedule_at', '<=', Carbon::now())
            ->whereIn('task_status', ['created', 'paused'])
            ->get();

        foreach ($tasks as $task) {
            // Call the execute method to handle the task execution
            $this->info("Executing task: {$task->task_name}");
            app(\App\Http\Controllers\TaskController::class)->executeTask($task->id);
        }
    }
}
