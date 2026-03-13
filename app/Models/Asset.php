<?php

namespace App\Models;

class Asset
{
    // placeholder for Eloquent model
    protected $table = 'assets';

    protected $fillable = [
        'name','tag','barcode','category_id','brand','model',
        'serial_number','purchase_date','warranty_expiry','status',
        'branch_id','assigned_user_id','notes','image_path'
    ];

    public function category() {}
    public function branch() {}
    public function assignedUser() {}
    public function movements() {}
}
