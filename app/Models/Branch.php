<?php

namespace App\Models;

class Branch
{
    protected $table = 'branches';
    protected $fillable = ['name','parent_id','type','address'];

    public function parent() {}
    public function children() {}
    public function assets() {}
}
