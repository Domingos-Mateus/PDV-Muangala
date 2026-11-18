<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\BillingReceipt;
use App\Models\CashSession;
use App\Models\Charge;
use App\Models\Service;
use App\Models\User;
use App\Services\BillingReport;
use App\Services\BillingService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    use ResolvesStore;

    // ---------- cobrar ----------

    public function collect()
    {
        $user = auth()->user();
        abort_unless($user->store_id, 403, 'Só utilizadores de uma loja recebem pagamentos.');

        if (! CashSession::where('user_id', $user->id)->where('status', 'open')->exists()) {
            return redirect()->route('cash.current')->withErrors('Abre o teu turno de caixa antes de receber.');
        }

        return view('billing.collect');
    }

    // ---------- devedores ----------

    public function debtors(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        $q = trim((string) $request->query('q'));

        $base = Charge::withoutGlobalScopes()
            ->where('charges.store_id', $storeId)
            ->where('charges.status', 'pending')
            ->whereDate('charges.due_date', '<', today())
            ->join('customers', 'customers.id', '=', 'charges.customer_id')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('customers.name', 'like', "%{$q}%")->orWhere('customers.unit', 'like', "%{$q}%")
                ->orWhere('customers.block', 'like', "%{$q}%")->orWhere('customers.phone', 'like', "%{$q}%")));

        $totalOwed = (float) (clone $base)->sum(DB::raw('charges.amount - charges.paid_amount'));

        $debtors = $base->groupBy('customers.id', 'customers.name', 'customers.block', 'customers.unit', 'customers.phone')
            ->selectRaw('customers.id, customers.name, customers.block, customers.unit, customers.phone,
                         COUNT(*) as n, SUM(charges.amount - charges.paid_amount) as owed, MIN(charges.due_date) as oldest')
            ->orderByDesc('owed')->paginate(25)->withQueryString();

        return view('billing.debtors', [
            'stores' => $this->switcherStores(), 'storeId' => $storeId, 'debtors' => $debtors, 'totalOwed' => $totalOwed,
        ]);
    }

    // ---------- relatório de cobranças ----------

    public function report(Request $request, BillingReport $report)
    {
        $storeId = $this->currentStoreId($request);
        $f = $this->filters($request, $storeId, $report);

        $receipts = $report->base($f)
            ->with(['user:id,name', 'customer:id,name,block,unit', 'payments'])
            ->latest('billing_receipts.id')->paginate(25)->withQueryString();

        return view('billing.report', [
            'stores'      => $this->switcherStores(),
            'storeId'     => $storeId,
            'f'           => $f,
            'receipts'    => $receipts,
            'totals'      => $report->totals($f),
            'overdue'     => $report->overdue($storeId),
            'byMethod'    => $report->byMethod($f),
            'collectors'  => $report->byCollector($f),
            'byService'   => $report->byService($f),
            'days'        => $report->byDay($f),
            'users'       => User::where('store_id', $storeId)->orderBy('name')->get(['id', 'name']),
            'serviceList' => Service::withoutGlobalScopes()->where('store_id', $storeId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request, BillingReport $report)
    {
        $storeId = $this->currentStoreId($request);
        $f = $this->filters($request, $storeId, $report);

        $query  = $report->base($f)->with(['user:id,name', 'customer', 'payments', 'allocations.charge']);
        $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência'];
        $money  = fn ($n) => number_format((float) $n, 2, ',', '');
        // evita que o Excel execute texto que comece por = + - @
        $safe   = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;

        return response()->streamDownload(function () use ($query, $labels, $money, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // para o Excel ler os acentos
            fputcsv($out, ['Recibo', 'Data', 'Cliente', 'Bloco/Apt.', 'Recebido por', 'Pagamento', 'Total', 'Estado', 'Períodos'], ';');

            $query->chunkById(500, function ($rows) use ($out, $labels, $money, $safe) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->number,
                        $r->created_at->format('d/m/Y H:i'),
                        $safe($r->customer?->name),
                        $safe(trim($r->customer?->block.' '.$r->customer?->unit)),
                        $safe($r->user?->name),
                        $r->payments->pluck('method')->map(fn ($m) => $labels[$m] ?? $m)->implode(' + '),
                        $money($r->total),
                        $r->status === 'cancelled' ? 'Cancelado' : 'Concluído',
                        $safe($r->allocations->map(fn ($a) => $a->charge?->description)->filter()->implode(' | ')),
                    ], ';');
                }
            }, 'id');

            fclose($out);
        }, 'cobrancas-'.$f['from'].'_'.$f['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(BillingReceipt $receipt)
    {
        return view('billing.show', [
            'r' => $receipt->load(['store', 'user:id,name', 'customer', 'payments', 'allocations.charge', 'session.register', 'canceller:id,name']),
        ]);
    }

    public function cancel(Request $request, BillingReceipt $receipt, BillingService $billing)
    {
        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:200']],
            ['reason.required' => 'Indica o motivo do cancelamento.']
        );

        try {
            $billing->cancel($receipt, auth()->user(), trim($data['reason']));
        } catch (DomainException $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('ok', 'Recibo cancelado. Os períodos voltaram a ficar por pagar.');
    }

    // ---------- recibo impresso ----------

    public function receipt(BillingReceipt $receipt)
    {
        $user = auth()->user();
        abort_unless($user->can('billing.view') || $receipt->user_id === $user->id, 403);

        return view('billing.receipt', [
            'r' => $receipt->load(['store', 'user:id,name', 'customer', 'payments', 'allocations.charge']),
        ]);
    }

    // ---------- auxiliares ----------

    private function filters(Request $request, int $storeId, BillingReport $report): array
    {
        $method = $request->query('method');
        $status = $request->query('status');

        return $report->normalize([
            'store_id'   => $storeId,
            'from'       => $request->query('from') ?: today()->startOfMonth()->toDateString(),
            'to'         => $request->query('to') ?: today()->toDateString(),
            'user_id'    => $request->query('user_id'),
            'q'          => trim((string) $request->query('q')),
            'service_id' => $request->query('service_id'),
            'method'     => in_array($method, ['cash', 'card', 'transfer'], true) ? $method : null,
            'status'     => in_array($status, ['completed', 'cancelled'], true) ? $status : null,
        ]);
    }
}
