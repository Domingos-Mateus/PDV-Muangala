<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateCharges extends Command
{
    protected $signature = 'billing:generate';
    protected $description = 'Gera as cobranças dos contratos que já venceram';

    public function handle(BillingService $billing): int
    {
        $n = 0;

        Subscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereDate('next_charge_date', '<=', today())
            ->each(function (Subscription $s) use ($billing, &$n) {
                $n += $billing->generate($s);
            });

        $this->info("Cobranças geradas: {$n}");

        return self::SUCCESS;
    }
}
