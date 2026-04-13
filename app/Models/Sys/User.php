<?php

namespace App\Models\Sys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class User extends Model
{
    use SoftDeletes;

    protected $table = 'sys_users';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'password',
        'is_active',
        'is_super_admin',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function tokens()
    {
        return $this->hasMany(UserToken::class, 'user_id');
    }
}
