<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'merchantcode',
        'name',
        'email',
        'mobile',
        'password',
        'remember_token',
        'otpverify',
        'otpresend',
        'mainwallet',
        'lockedamount',
        'role_id',
        'parent_id',
        'company_id',
        'scheme_id',
        'status',
        'address',
        'shopname',
        'gstin',
        'gender',
        'city',
        'state',
        'pincode',
        'pancard',
        'aadharcard',
        'pancardpic',
        'aadharcardpic',
        'gstpic',
        'profile',
        'profilepic',
        'kyc',
        'callbackurl',
        'remark',
        'resetpwd',
        'bank_holder_name',
        'account',
        'bank',
        'ifsc',
        'passwordold',
    ];
    public $with = ['role'];
    protected $appends = ['parents'];

    public function role(){
        return $this->belongsTo('App\Models\Role');
    }
    
    

    public function getParentsAttribute() {
        $user = User::where('id', $this->parent_id)->first(['id', 'name', 'mobile', 'role_id']);
        if($user){
            return $user->name." (".$user->id.")<br>".$user->mobile."<br>".$user->role->name;
        }else{
            return "Not Found";
        }
    }

    public function getUpdatedAtAttribute($value)
    {
        return date('d M y - h:i A', strtotime($value));
    }

    public function getCreatedAtAttribute($value)
    {
        return date('d M y - h:i A', strtotime($value));
    }
    protected $casts = [
        'mainwallet' => 'double',
        'lockedamount' => 'double',
    ];

    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
