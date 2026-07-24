<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunityLostReasonLog extends Model
{
    use HasFactory;
    protected $table = 'opportunity_lost_reason_logs';

    protected $fillable = [
        'company_id',
        'reason_id',
        'lost_remarks',
        'created_by'
    ];
}
