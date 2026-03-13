<?php

namespace App\Models;

class AssetMovement
{
    protected $table = 'asset_movements';
    protected $fillable = ['asset_id','from_branch_id','to_branch_id','user_id','notes','moved_at'];

    public function asset() {}
    public function user() {}
    public function fromBranch() {}
    public function toBranch() {}
}
