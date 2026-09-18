<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutingUsage extends Model
{
    use HasFactory;

    protected $table = 'routing_usage';

    protected $fillable = ['period_month', 'request_count'];
}
