<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_by',
        'supplier_id',
        'request_type',
        'expected_budget',
        'reason',
        'status',
        'manager_status',
        'committee_status',
        'rejection_reason',
    ];

    /*
    |------------------------------------
    | Relationships
    |------------------------------------
    */

    // رئيس المستودع اللي أنشأ الطلب
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // المورد المحتمل
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // مواد الطلب (1 - 5 مواد)
    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

   
}