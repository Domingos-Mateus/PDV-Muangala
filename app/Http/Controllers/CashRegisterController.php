<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashRegisterController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('cash.registers', [
            'stores'    => $this->switcherStores(),
            'storeId'   => $storeId,
            'registers' => CashRegister::where('store_id', $storeId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        CashRegister::create(['store_id' => $storeId, 'name' => $this->name($request, $storeId)]);

        return redirect()->route('cash.registers.index', ['store_id' => $storeId])->with('ok', 'Caixa criado.');
    }

    public function update(Request $request, CashRegister $register)
    {
        $register->update(['name' => $this->name($request, $register->store_id, $register)]);

        return redirect()->route('cash.registers.index', ['store_id' => $register->store_id])->with('ok', 'Caixa actualizado.');
    }

    public function toggle(CashRegister $register)
    {
        $register->update(['is_active' => ! $register->is_active]);

        return back()->with('ok', $register->is_active ? 'Caixa activado.' : 'Caixa desactivado.');
    }

    private function name(Request $request, int $storeId, ?CashRegister $register = null): string
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:60',
                Rule::unique('cash_registers', 'name')->where('store_id', $storeId)->ignore($register?->id)],
        ], ['name.unique' => 'Esta loja já tem um caixa com esse nome.'])['name'];
    }
}
