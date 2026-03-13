<?php

namespace App\Models;

class AssetCategory
{
    protected $table = 'asset_categories';
    protected $fillable = ['name','description'];

    public function assets() {}
}
