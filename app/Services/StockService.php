<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function in(int $storeId, int $productId, MovementType $type, float $qty,
        ?Model $reference = null, ?float $unitCost = null, ?string $note = null): StockMovement
    {
        return $this->move($storeId, $productId, $type, abs($qty), $reference, $unitCost, $note);
    }

    public function out(int $storeId, int $productId, MovementType $type, float $qty,
        ?Model $reference = null, ?float $unitCost = null, ?string $note = null): StockMovement
    {
        return $this->move($storeId, $productId, $type, -abs($qty), $reference, $unitCost, $note);
    }

    /** qty com sinal: positivo entra, negativo sai. */
    public function move(int $storeId, int $productId, MovementType $type, float $qty,
        ?Model $reference = null, ?float $unitCost = null, ?string $note = null,
        bool $allowNegative = false): StockMovement
    {
        return DB::transaction(function () use (
            $storeId, $productId, $type, $qty, $reference, $unitCost, $note, $allowNegative
        ) {
            // garante que a linha existe (1.ª entrada de um produto numa loja)
            ProductStock::withoutGlobalScopes()->firstOrCreate([
                'store_id'   => $storeId,
                'product_id' => $productId,
            ]);

            // bloqueia a linha até ao fim da transação
            $stock = ProductStock::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (float) $stock->qty;
            $after  = round($before + $qty, 3);

            if ($after < 0 && ! $allowNegative) {
                throw new InsufficientStockException(
                    "Estoque insuficiente (disponível: {$before}, pedido: ".abs($qty).")."
                );
            }

            $stock->update(['qty' => $after]);

            return StockMovement::create([
                'store_id'       => $storeId,
                'product_id'     => $productId,
                'user_id'        => auth()->id(),
                'type'           => $type,
                'qty'            => $qty,
                'qty_before'     => $before,
                'qty_after'      => $after,
                'unit_cost'      => $unitCost,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id'   => $reference?->getKey(),
                'note'           => $note,
            ]);
        });
    }
}
