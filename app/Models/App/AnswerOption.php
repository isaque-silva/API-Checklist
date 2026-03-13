<?php

namespace App\Models\App;

use App\Models\Chk\SelectionOption;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;

class AnswerOption extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'app_answer_options';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'answer_id', 'option_id', 'type', 'comment', 'is_selected', 'created_by', 'updated_by'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id = (string) Str::uuid());
    }

    public function option()
    {
        return $this->belongsTo(SelectionOption::class, 'option_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'answer_option_id');
    }
}
