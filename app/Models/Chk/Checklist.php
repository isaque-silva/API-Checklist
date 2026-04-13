<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class Checklist extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'chk_checklists';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'client_id',
        'title',
        'description',
        'email_group_id',
        'created_by',
        'updated_by',
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

    public function areas()
    {
        return $this->hasMany(Area::class, 'checklist_id');
    }

    public function emailGroup()
    {
        return $this->belongsTo(\App\Models\Sys\EmailGroup::class, 'email_group_id');
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Sys\Client::class, 'client_id');
    }

    public function scopeForClient($query, ?string $clientId)
    {
        if ($clientId) {
            return $query->where('client_id', $clientId);
        }
        return $query;
    }
}
