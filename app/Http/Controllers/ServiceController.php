<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('services.index', [
            'stores'   => $this->switcherStores(),
            'storeId'  => $storeId,
            'services' => Service::where('store_id', $storeId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        Service::create(['store_id' => $storeId, 'is_active' => true] + $this->data($request, $storeId));

        return redirect()->route('services.index', ['store_id' => $storeId])->with('ok', 'Serviço criado.');
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->data($request, $service->store_id, $service));

        return redirect()->route('services.index', ['store_id' => $service->store_id])->with('ok', 'Serviço actualizado.');
    }

    public function toggle(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);
        return back()->with('ok', $service->is_active ? 'Serviço activado.' : 'Serviço desactivado.');
    }

    private function data(Request $request, int $storeId, ?Service $service = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100',
                Rule::unique('services', 'name')->where('store_id', $storeId)->ignore($service?->id)],
            'default_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ], ['name.unique' => 'Já existe um serviço com esse nome nesta loja.']);
    }
}
