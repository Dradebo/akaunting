<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileSyncMapping extends Model
{
    protected $table = 'mobile_sync_mappings';

    protected $fillable = [
        'client_id',
        'server_id',
    'model',
    'server_version'
    ];
}
