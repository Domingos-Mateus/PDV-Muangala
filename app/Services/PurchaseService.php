<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Purchase;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private StockService $stock) {}

    public function create(array $data, array $items): Purchase
    {
        return DB::transaction(function () use ($data, $items) {
            $purchase = Purchase::create($data + ['status' => 'pending', 'total' => 0]);

            $total = 0;
            foreach ($items as $i) {
                $subtotal = round($i['qty'] * $i['unit_cost'], 2);
                $purchase->items()->create([
                    'product_id' => $i['product_id'],
                    'qty'        => $i['qty'],
                    'unit_cost'  => $i['unit_cost'],
                    'subtotal'   => $subtotal,
                ]);
                $total += $subtotal;
            }

            $purchase->update(['total' => $total]);
            return $purchase;
        });
    }

    public function receive(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            // bloqueia a compra para não ser recebida duas vezes
            $purchase = Purchase::withoutGlobalScopes()
                ->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== 'pending') {
                throw new DomainException('Esta compra já foi processada.');
            }

            foreach ($purchase->items as $item) {
                $this->stock->in(
                    $purchase->store_id, $item->product_id, MovementType::Purchase,
                    (float) $item->qty, $purchase, (float) $item->unit_cost
                );

                // guarda o último custo no catálogo
                $item->product()->update(['cost_price' => $item->unit_cost]);
            }

            $purchase->update(['status' => 'received', 'received_at' => now()]);
            return $purchase;
        });
    }
}
