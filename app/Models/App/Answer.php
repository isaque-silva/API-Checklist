<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;
use App\Models\Chk\Item;
use App\Models\App\AnswerOption;
use App\Models\App\Application;
use App\Models\Chk\SelectionOption;
use App\Models\Chk\ItemEvalOption;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'app_answers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'application_id', 
        'checklist_item_id', 
        'response_type',
        'response_text', 
        'response_number', 
        'response_datetime',
        'selected_option_id', 
        'comment',
        'created_by', 
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id = (string) Str::uuid());
    }

    /**
     * Resposta pertence a uma aplicação
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Resposta pertence a um item do checklist
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'checklist_item_id');
    }

    /**
     * Opções selecionadas para essa resposta
     */
    public function options()
    {
        return $this->hasMany(AnswerOption::class, 'answer_id');
    }

    /**
     * Opção selecionada (caso seja seleção única)
     */
    public function selectedOption()
    {
        return $this->belongsTo(SelectionOption::class, 'selected_option_id');
    }

    
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'answer_id');
    }
}
