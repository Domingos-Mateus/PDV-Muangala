<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\CashSession;
use App\Services\CashService;
use DomainException;
use Illuminate\Http\Request;

class CashController extends Controller
{
    use ResolvesStore;

    public function current(CashService $cash)
    {
        $user = auth()->user();
        abort_unless($user->store_id, 403, 'Só utilizadores de uma loja operam o caixa.');

        $session = $this->mySession();

        return view('cash.current', [
            'session'   => $session,
            'registers' => $session ? collect() : $cash->freeRegisters($user->store_id),
            'summary'   => $session ? $cash->summary($session) : null,
            'movements' => $session ? $session->movements()->with('user:id,name')->latest('id')->get() : collect(),
        ]);
    }

    public function open(Request $request, CashService $cash)
    {
        $data = $request->validate([
            'cash_register_id' => ['required', 'integer'],
            'opening_amount'   => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        try {
            $cash->open(auth()->user(), (int) $data['cash_register_id'], (float) $data['opening_amount']);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }

        return redirect()->route('cash.current')->with('ok', 'Turno aberto.');
    }

    public function movement(Request $request, CashService $cash)
    {
        $session = $this->mySession();
        if (! $session) {
            return redirect()->route('cash.current')->withErrors('Não tens um turno aberto.');
        }

        $data = $request->validate([
            'type'   => ['required', 'in:supply,withdrawal'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'reason' => ['required', 'string', 'max:200'],
        ], ['reason.required' => 'Indica o motivo.']);

        try {
            $cash->movement($session, $data['type'], (float) $data['amount'], trim($data['reason']));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }

        return back()->with('ok', 'Movimento registado.');
    }

    public function closeForm()
    {
        $session = $this->mySession();
        if (! $session) {
            return redirect()->route('cash.current')->withErrors('Não tens um turno aberto.');
        }

        return view('cash.close', ['session' => $session->load('register')]);
    }

    public function close(Request $request, CashService $cash)
    {
        $session = $this->mySession();
        if (! $session) {
            return redirect()->route('cash.current')->withErrors('Não tens um turno aberto.');
        }

        $data = $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $cash->close($session, (float) $data['counted_amount'], trim((string) ($data['notes'] ?? '')) ?: null);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }

        return redirect()->route('cash.current')->with('ok', 'Turno fechado.');
    }

    /** Histórico de turnos (gerente / super admin). */
    public function sessions(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        $sessions = CashSession::where('store_id', $storeId)
            ->with(['register', 'user:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('cash.sessions', [
            'stores'   => $this->switcherStores(),
            'storeId'  => $storeId,
            'sessions' => $sessions,
        ]);
    }

    private function mySession(): ?CashSession
    {
        return CashSession::where('user_id', auth()->id())->where('status', 'open')->with('register')->first();
    }
}
