<?php

namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class ItemEvalOption extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'chk_item_eval_options';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'checklist_item_id',
        'eval_option_id',
        'require_comment',
        'require_attachment',
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

    public function evalOption()
    {
        return $this->belongsTo(EvalOption::class, 'eval_option_id');
    }

}
