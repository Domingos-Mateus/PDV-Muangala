<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        $q = trim((string) $request->query('q'));

        $customers = Customer::where('store_id', $storeId)
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('block', 'like', "%{$q}%")->orWhere('unit', 'like', "%{$q}%")
                ->orWhere('nif', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('customers.index', [
            'stores' => $this->switcherStores(), 'storeId' => $storeId, 'customers' => $customers,
        ]);
    }

    public function create(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('customers.form', [
            'customer' => new Customer(['store_id' => $storeId, 'is_active' => true]),
            'storeId'  => $storeId,
        ]);
    }

    public function store(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        Customer::create(['store_id' => $storeId] + $this->data($request));

        return redirect()->route('customers.index', ['store_id' => $storeId])->with('ok', 'Cliente criado.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', ['customer' => $customer, 'storeId' => $customer->store_id]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->data($request));

        return redirect()->route('customers.index', ['store_id' => $customer->store_id])->with('ok', 'Cliente actualizado.');
    }

    public function toggle(Customer $customer)
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('ok', $customer->is_active ? 'Cliente activado.' : 'Cliente desactivado.');
    }

    private function data(Request $request): array
    {
        foreach (['name', 'phone', 'nif', 'block', 'unit', 'notes'] as $f) {
            $request->merge([$f => trim((string) $request->input($f)) ?: null]);
        }

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'nif'   => ['nullable', 'string', 'max:30'],
            'block' => ['nullable', 'string', 'max:40'],
            'unit'  => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
