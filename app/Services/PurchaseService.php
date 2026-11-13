<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private StockService $stock) {}

    public function create(array $data, array $items): Purchase
    {
        if (empty($items)) {
            throw new DomainException('A compra precisa de pelo menos um produto.');
        }

        $storeId    = (int) $data['store_id'];
        $productIds = collect($items)->pluck('product_id')->unique()->values();

        // tudo tem de ser da mesma loja
        $validProducts = Product::withoutGlobalScopes()
            ->where('store_id', $storeId)->whereIn('id', $productIds)->count();
        if ($validProducts !== $productIds->count()) {
            throw new DomainException('Há produtos que não pertencem a esta loja.');
        }

        $validSupplier = Supplier::withoutGlobalScopes()
            ->where('store_id', $storeId)->whereKey($data['supplier_id'])->exists();
        if (! $validSupplier) {
            throw new DomainException('O fornecedor não pertence a esta loja.');
        }

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
                ->lockForUpdate()->with('items')->findOrFail($purchase->id);

            if ($purchase->status !== 'pending') {
                throw new DomainException('Esta compra já foi processada.');
            }

            foreach ($purchase->items as $item) {
                $this->stock->in(
                    $purchase->store_id, $item->product_id, MovementType::Purchase,
                    (float) $item->qty, $purchase, (float) $item->unit_cost,
                    'Compra #'.$purchase->id
                );

                // último custo no produto (via model, para ficar no log)
                Product::withoutGlobalScopes()->find($item->product_id)
                    ?->update(['cost_price' => $item->unit_cost]);
            }

            $purchase->update(['status' => 'received', 'received_at' => now()]);
            return $purchase;
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::withoutGlobalScopes()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== 'pending') {
                throw new DomainException('Só é possível cancelar compras pendentes.');
            }

            $purchase->update(['status' => 'cancelled']);
            return $purchase;
        });
    }
}
