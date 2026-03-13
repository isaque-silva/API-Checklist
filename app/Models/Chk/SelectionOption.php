<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class SelectionOption extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'chk_selection_options';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'checklist_item_id',
        'value',
        'require_attachment',
        'require_comment',
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

    public function item()
    {
        return $this->belongsTo(Item::class, 'checklist_item_id');
    }
}
