<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\department; // Import Department model
use App\Models\Maintenance;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

     protected $fillable = [
        'id',
        'firstname',
        'middlename',
        'lastname',
        'email',
        'employee_id',
        'password',
        'birthdate',
        'usertype',
        'gender',
        'dept_id',
        'address',
        'contact',
        'is_deleted',
        'created_at',
        'remember_token',
        'userPicture',
    ];

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
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Get the department that the user belongs to.
     */
    public function department()
    {
        return $this->belongsTo(department::class, 'dept_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(Maintenance::class, 'requestor');
    }
}
