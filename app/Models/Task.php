<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';
    protected $fillable = [
        'created_ip',
        'api_url',
        'api_type',
        'api_payload',
        'api_parameters',
        'api_headers',
        'task_name',
        'task_status',
        'task_execute_at',
        'api_response',
        'response_email',
        'api_status',
        'failed_error',
        'api_status_code',
        'schedule_at',
    ];
    public $timestamps = true;

    protected $casts = [
        'api_payload' => 'array',
        'api_parameters' => 'array',
        'api_headers' => 'array'
    ];
}
