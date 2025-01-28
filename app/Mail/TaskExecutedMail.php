<?php
namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskExecutedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;

    /**
     * Create a new message instance.
     *
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Task Execution Result')
                    ->view('emails.task_executed')
                    ->with([
                        'taskName' => $this->task->task_name,
                        'taskStatus' => $this->task->task_status,
                        'taskResponse' => $this->task->api_response,
                        'taskError' => $this->task->failed_error,
                    ]);
    }
}
