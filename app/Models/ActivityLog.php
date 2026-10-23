<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];
    protected $casts = ['properties' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public static function record(?Model $subject, string $action, string $description, array $props = [], ?int $storeId = null, ?int $userId = null): void
    {
        try {
            $storeId ??= $subject instanceof Store
                ? $subject->id
                : ($subject?->store_id ?? (app()->bound('currentStore') ? app('currentStore')?->id : null));

            static::create([
                'store_id'     => $storeId,
                'user_id'      => $userId ?? auth()->id(),
                'action'       => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id'   => $subject?->getKey(),
                'description'  => $description,
                'properties'   => $props ?: null,
                'ip'           => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e); // o registo nunca pode partir a aplicação
        }
    }
}
