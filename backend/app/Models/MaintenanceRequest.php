<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'ticket_number';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
