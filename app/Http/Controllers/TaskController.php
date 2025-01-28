<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
   

    // Method to store a new task
    public function store(Request $request)
    {
        $validated = $request->validate([
            'api_url' => 'required|string',
            'api_type' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'task_name' => 'required|string',
            'schedule_at' => 'nullable|date',
            // Add other validations as necessary
        ]);

        $task = Task::create([
            'created_ip' => $request->ip(),
            'api_url' => $validated['api_url'],
            'api_type' => $validated['api_type'],
            'api_payload' => $request->input('api_payload', null),
            'api_parameters' => $request->input('api_parameters', null),
            'api_headers' => $request->input('api_headers', null),
            'task_name' => $validated['task_name'],
            'task_status' => 'created',
            'task_execute_at' => $validated['schedule_at'],
            'api_status' => 'pending',
            'response_email' => $request->input('response_email', null),
            'api_status_code' => null,
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task
        ], 201);
    }

    // Method to retrieve tasks
    public function index()
    {
        $tasks = Task::all();
        return response()->json($tasks);
    }

    // Method to update task status
    public function updateStatus($id, Request $request)
    {
        $task = Task::findOrFail($id);
        $task->update([
            'task_status' => $request->input('task_status', $task->task_status),
            'api_status_code' => $request->input('api_status_code', $task->api_status_code),
            'api_response' => $request->input('api_response', $task->api_response),
            'api_status' => $request->input('api_status', $task->api_status),
            'failed_error' => $request->input('failed_error', $task->failed_error),
        ]);

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task
        ]);
    }

    public function executeTask($id)
    {
        $task = Task::findOrFail($id);

        // Make the API call based on the task's details
        try {
            // Construct the API call based on the task's type (GET, POST, etc.)
            $response = null;
            $status_code = null;

            switch (strtoupper($task->api_type)) {
                case 'POST':
                    $response = Http::withHeaders($task->api_headers)->post($task->api_url, $task->api_payload);
                    break;
                case 'GET':
                    $response = Http::withHeaders($task->api_headers)->get($task->api_url, $task->api_parameters);
                    break;
                case 'PUT':
                    $response = Http::withHeaders($task->api_headers)->put($task->api_url, $task->api_payload);
                    break;
                case 'DELETE':
                    $response = Http::withHeaders($task->api_headers)->delete($task->api_url, $task->api_parameters);
                    break;
                case 'PATCH':
                    $response = Http::withHeaders($task->api_headers)->patch($task->api_url, $task->api_payload);
                    break;
                default:
                    throw new \Exception("Unsupported API method");
            }

            $status_code = $response->status(); // Get the status code from the response
            $api_response = $response->json(); // Get the response body as JSON

            // Update task with the API response
            $task->update([
                'task_status' => 'completed',
                'api_status' => 'success',
                'api_status_code' => $status_code,
                'api_response' => ($api_response),
            ]);

            // Optionally, send email with the response (if response_email is provided)
            if ($task->response_email) {
                // Use a mail service to send the response back to the user
                \Mail::to($task->response_email)->send(new TaskExecutedMail($task));
            }

            return response()->json([
                'message' => 'Task executed successfully.',
                'task' => $task
            ]);

        } catch (\Exception $e) {
            // In case of an error, log and update task status
            Log::error('Error executing task: ' . $e->getMessage());

            $task->update([
                'task_status' => 'failed',
                'failed_error' => $e->getMessage(),
                'api_status' => 'failure',
            ]);

            return response()->json([
                'message' => 'Task execution failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
