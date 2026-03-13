<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;

class ItemTypeInputMask extends Model
{
    protected $table = 'chk_item_type_input_masks';

    protected $fillable = [
        'item_type_id',
        'input_mask_id',
        'selection_type',
        'allow_attachment',
        'require_attachment',
        'allow_comment',
        'require_comment',
        'evaluative_option_group_id'
    ];

    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function inputMask()
    {
        return $this->belongsTo(InputMask::class, 'input_mask_id');
    }

}
