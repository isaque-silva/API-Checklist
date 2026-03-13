<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasAudit;
use App\Models\Chk\Checklist;
use App\Models\Sys\EmailGroup;

class Application extends Model
{
    use SoftDeletes, HasAudit;

    protected $table = 'app_applications';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'checklist_id', 'status', 'applied_at', 'completed_at', 'number',
        'created_by', 'updated_by',
    ];
    
    protected $casts = [
        'number' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
            
            if (empty($model->number)) {
                $model->number = self::generateNextNumber();
            }
        });
    }

    /**
     * Gera o próximo número sequencial de forma segura
     */
    protected static function generateNextNumber(): int
    {
        return (int) \DB::transaction(function () {
            // Tenta obter o próximo número em uma transação segura
            $lastNumber = (int) \DB::table('app_applications')
                ->lockForUpdate()
                ->max('number');

            return $lastNumber + 1;
        });
    }
    public function getNumeroFormatadoAttribute()
    {
        return str_pad($this->number, 7, '0', STR_PAD_LEFT);
    } 
    public function answers()
    {
        return $this->hasMany(Answer::class, 'application_id');
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    public function emailGroup()
    {
        return $this->belongsTo(EmailGroup::class, 'email_group_id');
    }

    // Accessor para herdar o email_group_id do checklist relacionado
   // public function getEmailGroupIdAttribute()
    //{
    //    return $this->checklist ? $this->checklist->email_group_id : null;
    //}
}
