<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Company extends Model
{
    use HasFactory;
    protected $table    = 'company_list';
    protected $fillable = [
        'id',
        'assigned_psc',
        'company_name',
        'company_id',
        'address',
        'created_at',
        'updated_at'
    ];

    public function participants()
    {
        return $this->hasMany(Participants::class, 'company_id');
    }

        public function latestUpdate()
    {
        return $this->hasOne(\App\Models\ParticipantsUpdate::class, 'participant_id') // dito participant_id = company_id
                    ->latest('update_date'); // pinaka-latest update
    }

    public function assignedAgent() {
        return $this->hasOne(AssignedAgent::class, 'company_id', 'id'); // or kung ibang key yung relation
    }
   
}
