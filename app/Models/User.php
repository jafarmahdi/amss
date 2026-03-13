<?php

namespace App\Models;

class User
{
    protected $table = 'users';
    protected $fillable = ['name','email','password','role'];

    public function assets() {}
    public function movements() {}
}
