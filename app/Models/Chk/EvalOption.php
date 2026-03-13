<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EvalOption extends Model
{
    use SoftDeletes;

    protected $table = 'chk_eval_options';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'checklist_item_id',
        'evaluative_option_group_id',
        'option_value',
        'require_attachment',
        'require_comment',
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

    public function item()
    {
        return $this->belongsTo(Item::class, 'checklist_item_id');
    }

    public function group()
    {
        return $this->belongsTo(EvalOptionGroup::class, 'evaluative_option_group_id');
    }
}
