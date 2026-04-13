<?php

namespace App\Traits;

trait HasAudit
{
    public static function bootHasAudit()
    {
        static::creating(function ($model) {
            $user = request()->get('auth_user');
            $login = $user ? $user->email : request()->get('login');
            $model->created_by = $login;
            $model->updated_by = $login;
        });

        static::updating(function ($model) {
            $user = request()->get('auth_user');
            $login = $user ? $user->email : request()->get('login');
            $model->updated_by = $login;
        });

        static::deleting(function ($model) {
            if ($model->usesSoftDeletes()) {
                $user = request()->get('auth_user');
                $login = $user ? $user->email : request()->get('login');
                $model->updated_by = $login;
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
