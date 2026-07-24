<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInquiryLogs extends Model
{
    use HasFactory;
    protected $table = 'product_inquiry_logs';

    protected $fillable = [
        'company_id',
        'product_id',
        'product_remarks',
        'created_by'
    ];
}
