<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $me     = auth()->user();
        $stores = $me->is_super_admin ? Store::orderBy('name')->get() : collect();

        $query = ActivityLog::with(['user', 'store'])->latest('id');

        if ($me->is_super_admin) {
            $query->when($request->integer('store_id'), fn ($q, $id) => $q->where('store_id', $id));
        } else {
            $query->where('store_id', $me->store_id);
        }

        $query->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
              ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
              ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
              ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')));

        $users = User::where('is_super_admin', false)
            ->when(!$me->is_super_admin, fn ($q) => $q->where('store_id', $me->store_id))
            ->orderBy('name')->get(['id', 'name']);

        return view('logs.index', [
            'logs'   => $query->paginate(30)->withQueryString(),
            'stores' => $stores,
            'users'  => $users,
            'labels' => self::LABELS,
        ]);
    }

    public const LABELS = [
        'created' => 'Criou', 'updated' => 'Editou', 'deleted' => 'Apagou',
        'permissions' => 'Permissões', 'login' => 'Entrada', 'logout' => 'Saída',
        'login_failed' => 'Login falhado',
    ];
}
