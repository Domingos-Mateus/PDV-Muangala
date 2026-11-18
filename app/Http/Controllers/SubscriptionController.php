<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        $q = trim((string) $request->query('q'));

        $subs = Subscription::where('store_id', $storeId)->with(['customer', 'service'])
            ->when($q, fn ($query) => $query->whereHas('customer', fn ($c) => $c
                ->where('name', 'like', "%{$q}%")->orWhere('unit', 'like', "%{$q}%")->orWhere('block', 'like', "%{$q}%")))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('id')->paginate(25)->withQueryString();

        return view('subscriptions.index', [
            'stores' => $this->switcherStores(), 'storeId' => $storeId, 'subs' => $subs,
        ]);
    }

    public function create(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('subscriptions.create', [
            'storeId'   => $storeId,
            'customers' => Customer::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => trim($c->name.' · '.$c->block.' '.$c->unit.' · '.$c->phone, ' ·')]),
            'services'  => Service::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'default_price']),
        ]);
    }

    public function store(Request $request, BillingService $billing)
    {
        $storeId = $this->currentStoreId($request);

        $data = $request->validate([
            'customer_id'    => ['required', Rule::exists('customers', 'id')->where('store_id', $storeId)],
            'service_id'     => ['required', Rule::exists('services', 'id')->where('store_id', $storeId)],
            'price'          => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'frequency'      => ['required', 'in:daily,weekly,monthly'],
            'interval_count' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date'     => ['required', 'date', 'after_or_equal:'.today()->subYear()->toDateString()],
        ], [
            'customer_id.required' => 'Escolhe o cliente na lista.',
            'service_id.required'  => 'Escolhe o serviço.',
        ]);

        $sub = Subscription::create($data + [
            'store_id' => $storeId, 'next_charge_date' => $data['start_date'], 'status' => 'active',
        ]);

        // gera já as cobranças em atraso e a do período actual
        $n = $billing->generate($sub);

        return redirect()->route('subscriptions.index', ['store_id' => $storeId])
            ->with('ok', "Contrato criado. Cobranças geradas: {$n}.");
    }

    public function status(Request $request, Subscription $subscription)
    {
        $status = $request->validate(['status' => ['required', 'in:active,paused,ended']])['status'];

        $subscription->update($status === 'ended'
            ? ['status' => 'ended', 'end_date' => today()]
            : ['status' => $status]);

        return back()->with('ok', 'Contrato actualizado.');
    }
}
