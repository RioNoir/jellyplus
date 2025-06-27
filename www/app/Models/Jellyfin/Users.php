<?php

namespace App\Models\Jellyfin;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $connection = 'jellyfin';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $table = 'Users';
}
