<?php

namespace App\Models;

use App\Traits\Authenticatable;
use App\Traits\MakeUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model 
{
    use Authenticatable, HasFactory, HasUuids, MakeUser;

    protected $fillable = ['name', 'email'];
}
