<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class Attachment extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'app_attachments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'answer_option_id',
        'answer_id',
        'file_path',
        'file_name',
        'original_size',
        'compressed_size',
        'is_compressed',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function answerOption()
    {
        return $this->belongsTo(AnswerOption::class, 'answer_option_id');
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class, 'answer_id');
    }
}
