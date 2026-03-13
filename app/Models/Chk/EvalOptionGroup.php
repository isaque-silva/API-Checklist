<?php
namespace App\Models\Chk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EvalOptionGroup extends Model
{
    use SoftDeletes;

    protected $table = 'chk_eval_option_groups';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
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

    public function options()
    {
        return $this->hasMany(\App\Models\Chk\EvalOption::class, 'evaluative_option_group_id');
    }
}
