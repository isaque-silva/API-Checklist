<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ItemType extends Model
{

    protected $table = 'chk_item_types';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'label',
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

    
    public function ItemTypeInputMask()
    {
        return $this->hasMany(ItemTypeInputMask::class, 'item_type_id', 'id');
    }

}
