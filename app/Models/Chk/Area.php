<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class Area extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'chk_areas';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'checklist_id',
        'title',
        'description',
        'order',
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

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'checklist_area_id');
    }
}
