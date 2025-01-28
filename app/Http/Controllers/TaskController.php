<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Mail\TaskExecutedMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class TaskController extends Controller
{


    // Method to store a new task
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_url' => 'required|url', 
            'api_type' => 'required|in:GET,POST,PUT,DELETE',  
            'api_payload' => 'nullable|json',  
            'api_parameters' => 'nullable|json',  
            'api_headers' => 'nullable|json', 
            'task_name' => 'required|string|max:255',  
            // 'schedule_at' => 'required|date', 
            'schedule_at' => 'required|date_format:Y-m-d H:i:s',
            'response_email' => 'nullable|email', 
        ]);
    
        // If validation fails, return errors in JSON format
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        $task = Task::create([
            'created_ip' => $request->ip(),
            'api_url' => $validated['api_url'],
            'api_type' => $validated['api_type'],
            'api_payload' => $request->input('api_payload', null),
            'api_parameters' => $request->input('api_parameters', null),
            'api_headers' => $request->input('api_headers', null),
            'task_name' => $validated['task_name'],
            'task_status' => 'created',
            'schedule_at' => $validated['schedule_at'],
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
    public function index(Request $request)
    {
        try {
            // Validate the input parameters for api_status and task_status
            $validator = Validator::make($request->all(), [
                'api_status' => 'nullable|in:pending,success,failure,in_progress', // api_status must be valid
                'task_status' => 'nullable|in:created,completed,failed,paused,other,none', // task_status must be valid
                'response_email' => 'nullable|email', // response_email must be a valid email format
            ]);
    
            // If validation fails, return a JSON response with errors
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }
    
            // Retrieve filter parameters from the request
            $apiStatus = $request->input('api_status');
            $taskStatus = $request->input('task_status');
            $responseEmail = $request->input('response_email');
    
            // Start building the query
            $query = Task::query();
    
            // Apply filters if parameters are present
            if ($apiStatus) {
                $query->where('api_status', $apiStatus);
            }
    
            if ($taskStatus) {
                $query->where('task_status', $taskStatus);
            }
    
            if ($responseEmail) {
                $query->where('response_email', 'like', '%' . $responseEmail . '%'); // Partial match for email
            }
    
            // Apply pagination (e.g., 10 results per page)
            $tasks = $query->paginate(10);
    
            // Return the paginated tasks as a JSON response
            return response()->json($tasks);
    
        } catch (\Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Method to update task status
    public function updateStatus($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_status' => 'nullable|in:created,completed,failed,paused,other,none', 
            'api_status_code' => 'nullable|integer',
            'api_response' => 'nullable|string', 
            'api_status' => 'nullable|in:pending,success,failure,in_progress',
            'failed_error' => 'nullable|string', 
            'response_email' => 'nullable|email',
            'task_execute_at' => 'nullable|date_format:Y-m-d H:i:s', 
        ]);
    
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }
    
 
        try {
            // Find the task or fail if not found
            $task = Task::findOrFail($id);
    

            $task->update([
                'task_status' => $request->input('task_status', $task->task_status),
                'api_status_code' => $request->input('api_status_code', $task->api_status_code),
                'api_response' => $request->input('api_response', $task->api_response),
                'api_status' => $request->input('api_status', $task->api_status),
                'failed_error' => $request->input('failed_error', $task->failed_error),
                'response_email' => $request->input('response_email', $task->response_email),
                'task_execute_at' => $request->input('task_execute_at', $task->task_execute_at),
            ]);
    

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'task' => $task,
            ], 200);
    
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function executeTask($id)
    {
        $task = Task::findOrFail($id);

        // Make the API call based on the task's details
        try {
            // Construct the API call based on the task's type (GET, POST, etc.)
            $response = null;
            $status_code = null;
            $headers = $task->api_headers ? $task->api_headers : [];
            $payload = $task->api_payload ? $task->api_payload : [];

            $apiReq = $response = Http::withHeaders($headers)
            ->withoutVerifying(); //Disable SSL on Local Testing only
            switch (strtoupper($task->api_type)) {

                case 'POST':
                    $response = $apiReq->post($task->api_url, $payload);
                    break;
                case 'GET':
                    $response = $apiReq->get($task->api_url, $task->api_parameters);
                    break;
                case 'PUT':
                    $response = $apiReq->put($task->api_url, $payload);
                    break;
                case 'DELETE':
                    $response = $apiReq->delete($task->api_url, $task->api_parameters);
                    break;
                case 'PATCH':
                    $response = $apiReq->patch($task->api_url, $payload);
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

            
            if ($task->response_email) {
                //Send email to user 
                //Add Email to Que to send 
                Mail::to($task->response_email)->queue(new TaskExecutedMail($task));
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
