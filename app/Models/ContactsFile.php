<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactsFile extends Model
{
    use HasFactory;
    protected $table    = 'contacts_files';
    protected $fillable = [
        'company_id',
        'status_id',
        'file_path',
        'file_name',
        'file_type',
        'uploaded_by',
        'uploaded_at'
    ];

    public $timestamps = true;
    public function contacts()
    {
        return $this->belongsTo(Contact::class, 'company_id');
    }
}
