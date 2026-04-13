<?php

namespace App\Models\Sys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class Client extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'sys_clients';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function users()
    {
        return $this->hasMany(User::class, 'client_id');
    }

    public function checklists()
    {
        return $this->hasMany(\App\Models\Chk\Checklist::class, 'client_id');
    }

    public function applications()
    {
        return $this->hasMany(\App\Models\App\Application::class, 'client_id');
    }
}
