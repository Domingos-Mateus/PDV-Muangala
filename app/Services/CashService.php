<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CashService
{
    /** Caixas activos da loja que não têm turno aberto. */
    public function freeRegisters(int $storeId)
    {
        $busy = CashSession::withoutGlobalScopes()->where('status', 'open')->pluck('cash_register_id');

        return CashRegister::withoutGlobalScopes()
            ->where('store_id', $storeId)->where('is_active', true)
            ->whereNotIn('id', $busy)->orderBy('name')->get();
    }

    public function open(User $user, int $registerId, float $opening): CashSession
    {
        if (! $user->store_id) {
            throw new DomainException('Só utilizadores de uma loja abrem caixa.');
        }

        return DB::transaction(function () use ($user, $registerId, $opening) {
            // bloqueia o utilizador e o caixa: dois cliques ao mesmo tempo não abrem dois turnos
            User::whereKey($user->id)->lockForUpdate()->first();

            $register = CashRegister::withoutGlobalScopes()
                ->where('store_id', $user->store_id)->where('is_active', true)
                ->lockForUpdate()->find($registerId);

            if (! $register) {
                throw new DomainException('Caixa inválido.');
            }
            if (CashSession::withoutGlobalScopes()->where('cash_register_id', $register->id)->where('status', 'open')->exists()) {
                throw new DomainException('Este caixa já tem um turno aberto.');
            }
            if (CashSession::withoutGlobalScopes()->where('user_id', $user->id)->where('status', 'open')->exists()) {
                throw new DomainException('Já tens um turno aberto.');
            }

            return CashSession::create([
                'store_id'         => $user->store_id,
                'cash_register_id' => $register->id,
                'user_id'          => $user->id,
                'status'           => 'open',
                'opening_amount'   => round($opening, 2),
                'opened_at'        => now(),
            ]);
        });
    }

    /** Valores do turno. 'expected' é o dinheiro que devia estar na gaveta. */
    public function summary(CashSession $session): array
    {
        $cashSales = (float) DB::table('payments')
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.cash_session_id', $session->id)
            ->where('sales.status', 'completed')
            ->where('payments.method', 'cash')
            ->sum('payments.amount');

        $moves = CashMovement::withoutGlobalScopes()->where('cash_session_id', $session->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type='supply' THEN amount END),0) as supplies,
                         COALESCE(SUM(CASE WHEN type='withdrawal' THEN amount END),0) as withdrawals")
            ->first();

        $supplies    = (float) $moves->supplies;
        $withdrawals = (float) $moves->withdrawals;

        return [
            'opening'     => (float) $session->opening_amount,
            'cash_sales'  => round($cashSales, 2),
            'supplies'    => $supplies,
            'withdrawals' => $withdrawals,
            'expected'    => round((float) $session->opening_amount + $cashSales + $supplies - $withdrawals, 2),
        ];
    }

    public function movement(CashSession $session, string $type, float $amount, string $reason): CashMovement
    {
        return DB::transaction(function () use ($session, $type, $amount, $reason) {
            $s = CashSession::withoutGlobalScopes()->lockForUpdate()->findOrFail($session->id);

            if ($s->status !== 'open') {
                throw new DomainException('Este turno já está fechado.');
            }
            if ($amount <= 0) {
                throw new DomainException('O valor tem de ser maior que zero.');
            }
            if ($type === 'withdrawal' && $amount > $this->summary($s)['expected']) {
                throw new DomainException('A sangria é maior que o dinheiro que está no caixa.');
            }

            return CashMovement::create([
                'store_id'        => $s->store_id,
                'cash_session_id' => $s->id,
                'user_id'         => auth()->id(),
                'type'            => $type,
                'amount'          => round($amount, 2),
                'reason'          => $reason,
            ]);
        });
    }

    public function close(CashSession $session, float $counted, ?string $notes): CashSession
    {
        return DB::transaction(function () use ($session, $counted, $notes) {
            $s = CashSession::withoutGlobalScopes()->lockForUpdate()->findOrFail($session->id);

            if ($s->status !== 'open') {
                throw new DomainException('Este turno já está fechado.');
            }

            $expected = $this->summary($s)['expected'];
            $diff     = round($counted - $expected, 2);

            if ($diff != 0.0 && blank($notes)) {
                throw new DomainException('O valor contado não bate com o esperado. Explica o motivo nas notas.');
            }

            $s->update([
                'status'          => 'closed',
                'expected_amount' => $expected,
                'counted_amount'  => round($counted, 2),
                'difference'      => $diff,
                'notes'           => $notes,
                'closed_at'       => now(),
            ]);

            return $s;
        });
    }
}
