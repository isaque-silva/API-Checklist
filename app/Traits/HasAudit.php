<?php

namespace App\Traits;

trait HasAudit
{
    public static function bootHasAudit()
    {
        static::creating(function ($model) {
            $model->created_by = request()->get('login');
            $model->updated_by = request()->get('login');
        });

        static::updating(function ($model) {
            $model->updated_by = request()->get('login');
        });

        static::deleting(function ($model) {
            if ($model->usesSoftDeletes()) {
                $model->updated_by = request()->get('login');
                $model->updated_at = now();
                $model->save();
            }
        });
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class));
    }
}
