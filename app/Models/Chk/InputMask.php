<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InputMask extends Model
{
    use SoftDeletes;

    protected $table = 'chk_input_masks';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'label',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // public function itemTypes()
    // {
    //     return $this->belongsToMany(ItemType::class, 'chk_item_type_input_masks', 'input_mask_id', 'item_type_id');
    // }

    public function itemTypeInputMasks()
    {
        return $this->hasMany(ItemTypeInputMask::class, 'input_mask_id', 'id');
    }

}
