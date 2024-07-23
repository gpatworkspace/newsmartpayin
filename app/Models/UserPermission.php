<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class UserPermission extends Model
{
	use LogsActivity;
    protected $fillable = ['user_id', 'permission_id'];

    protected static $logAttributes = ['user_id', 'permission_id'];
    protected static $logOnlyDirty = true;
    
    public $timestamps = false;
}
