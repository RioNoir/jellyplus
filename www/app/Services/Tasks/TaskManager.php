<?php

namespace App\Services\Tasks;

use App\Jobs\CommandExecutionJob;
use App\Jobs\Job;
use Carbon\Carbon;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class TaskManager
{
    protected $task;

    public function __construct(string $taskId){
        $this->task = @self::getTaskList()[$taskId];
    }

    public static function getTaskList(){
        return jp_config('tasks.list');
    }

    public function getTask(){
        return $this->task;
    }

    public function exists(): bool {
        return isset($this->task);
    }

    public function executeTask(): bool {
        try {
            Log::info("Executing Task: " . $this->task['Key']);
            dispatch(new CommandExecutionJob($this->task['Key']));
            return true;
        }catch (\Exception $e){
            Log::info("Failed Executing Task: " . $this->task['Key']);
            return false;
        }
    }

}
