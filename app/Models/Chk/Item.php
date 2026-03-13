<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class Item extends Model
{
    use SoftDeletes, HasAudit;
    
    protected $table = 'chk_items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'checklist_area_id',
        'evaluative_option_group_id',
        'item_type_id',
        'input_mask_id',
        'name',
        'filter',
        'selection_type',
        'allow_attachment',
        'allow_comment',
        'require_comment',
        'require_attachment',

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

    public function area()
    {
        return $this->belongsTo(Area::class, 'checklist_area_id');
    }

    public function type()
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function mask()
    {
        return $this->belongsTo(InputMask::class, 'input_mask_id');
    }

    public function evalOptions()
    {
        return $this->hasMany(ItemEvalOption::class, 'checklist_item_id');
    }


    public function selectionOptions()
    {
        return $this->hasMany(SelectionOption::class, 'checklist_item_id');
    }

    public function evalGroup()
    {
        return $this->belongsTo(EvalOptionGroup::class, 'eval_option_group_id');
    }
    
    public function evalOption()
    {
        return $this->belongsTo(EvalOption::class, 'eval_option_id');
    }
}
