<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;

trait LogsActivity
{
    protected static array $logLabels = [
        'Store' => 'Loja', 'User' => 'Utilizador', 'Role' => 'Perfil',
        'Category' => 'Categoria', 'Product' => 'Produto', 'Supplier' => 'Fornecedor',
        'Purchase' => 'Compra', 'ProductStock' => 'Stock',
    ];

    /** Campos que nunca se gravam em claro. */
    protected function logHidden(): array
    {
        return ['password', 'remember_token'];
    }

    protected static function bootLogsActivity(): void
    {
        static::created(function ($m) {
            ActivityLog::record($m, 'created', $m->logTitle(), ['depois' => $m->maskedLog($m->getAttributes())]);
        });

        static::updated(function ($m) {
            $changes = Arr::except($m->getChanges(), ['updated_at']);
            if (!$changes) {
                return;
            }
            $old = Arr::only($m->getOriginal(), array_keys($changes));
            ActivityLog::record($m, 'updated', $m->logTitle(), [
                'antes'  => $m->maskedLog($old),
                'depois' => $m->maskedLog($changes),
            ]);
        });

        static::deleted(function ($m) {
            ActivityLog::record($m, 'deleted', $m->logTitle());
        });
    }

    protected function maskedLog(array $data): array
    {
        $data = Arr::except($data, ['created_at', 'updated_at']);
        foreach ($this->logHidden() as $k) {
            if (array_key_exists($k, $data)) {
                $data[$k] = '***';
            }
        }
        return $data;
    }

    public function logTitle(): string
    {
        $label = static::$logLabels[class_basename($this)] ?? class_basename($this);
        return $label . ' «' . ($this->name ?? $this->getKey()) . '»';
    }
}
