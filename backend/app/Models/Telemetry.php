<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;
}
