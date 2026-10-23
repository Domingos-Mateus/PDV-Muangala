<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $me      = auth()->user();
        $stores  = $me->is_super_admin ? Store::orderBy('name')->get() : collect();
        $storeId = $me->is_super_admin
            ? ($request->integer('store_id') ?: $stores->first()?->id)
            : $me->store_id;

        $users = User::with('role')
            ->where('store_id', $storeId)
            ->where('is_super_admin', false)
            ->orderBy('name')->get();

        return view('users.index', compact('stores', 'storeId', 'users'));
    }

    public function create(Request $request)
    {
        $storeId = $this->resolveStoreId($request);
        return $this->form(new User(['store_id' => $storeId, 'is_active' => true]), $storeId);
    }

    public function store(Request $request)
    {
        $storeId = $this->resolveStoreId($request);
        $data    = $this->validated($request, $storeId);

        $user = new User();
        $user->forceFill([
            'store_id'       => $storeId,
            'role_id'        => $data['role_id'],
            'name'           => $data['name'],
            'username'       => $data['username'],
            'email'          => $data['email'],
            'employee_code'  => $data['employee_code'],
            'password'       => Hash::make($data['password']),
            'is_super_admin' => false,
            'is_active'      => $request->boolean('is_active'),
        ])->save();

        return redirect()->route('users.index', ['store_id' => $storeId])->with('ok', 'Utilizador criado.');
    }

    public function edit(User $user)
    {
        $this->ensureAccess($user);
        return $this->form($user, $user->store_id);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAccess($user);
        $data = $this->validated($request, $user->store_id, $user);

        $fill = [
            'role_id'       => $data['role_id'],
            'name'          => $data['name'],
            'username'      => $data['username'],
            'email'         => $data['email'],
            'employee_code' => $data['employee_code'],
        ];

        if (!empty($data['password'])) {
            $fill['password'] = Hash::make($data['password']);
        }

        // ninguém se desactiva a si próprio
        if ($user->id !== auth()->id()) {
            $fill['is_active'] = $request->boolean('is_active');
        }

        $user->forceFill($fill)->save();

        return redirect()->route('users.index', ['store_id' => $user->store_id])->with('ok', 'Utilizador actualizado.');
    }

    public function toggle(User $user)
    {
        $this->ensureAccess($user);
        abort_if($user->id === auth()->id(), 403, 'Não podes desactivar a tua própria conta.');

        $user->forceFill(['is_active' => !$user->is_active])->save();

        return back()->with('ok', $user->is_active ? 'Utilizador activado.' : 'Utilizador desactivado.');
    }

    // ---------- auxiliares ----------

    private function form(User $user, int $storeId)
    {
        return view('users.form', [
            'user'    => $user,
            'storeId' => $storeId,
            'store'   => Store::find($storeId),
            'roles'   => $this->assignableRoles($storeId),
        ]);
    }

    private function validated(Request $request, int $storeId, ?User $user = null): array
    {
        $request->merge([
            'name'          => trim((string) $request->input('name')),
            'username'      => strtolower(trim((string) $request->input('username'))),
            'email'         => trim((string) $request->input('email')) ?: null,
            'employee_code' => trim((string) $request->input('employee_code')) ?: null,
        ]);

        return $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'username' => [
                'required', 'string', 'max:40', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'employee_code' => ['nullable', 'string', 'max:40'],
            'role_id'       => ['required', Rule::in($this->assignableRoles($storeId)->pluck('id')->all())],
            'password'      => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.unique' => 'Este username já está em uso.',
            'email.unique'    => 'Este email já está em uso.',
            'role_id.in'      => 'Perfil inválido para esta loja.',
            'role_id.required'=> 'Escolhe um perfil.',
        ]);
    }

    private function resolveStoreId(Request $request): int
    {
        $me = auth()->user();
        $id = $me->is_super_admin ? $request->integer('store_id') : $me->store_id;
        abort_unless($id && Store::whereKey($id)->exists(), 404);
        return $id;
    }

    /** Perfis da loja que o utilizador actual pode atribuir. */
    private function assignableRoles(int $storeId)
    {
        $me    = auth()->user();
        $roles = Role::with('permissions')->where('store_id', $storeId)->orderBy('name')->get();

        if ($me->is_super_admin) {
            return $roles;
        }

        $mine = $me->role?->permissions()->pluck('key') ?? collect();

        return $roles->filter(fn ($r) => $r->permissions->pluck('key')->diff($mine)->isEmpty())->values();
    }

    private function ensureAccess(User $user): void
    {
        $me = auth()->user();

        abort_if($user->is_super_admin && !$me->is_super_admin, 403);

        if ($me->is_super_admin) {
            return;
        }

        abort_unless($user->store_id === $me->store_id, 403);

        // o gerente não mexe em quem tem mais poder do que ele (excepto na própria conta)
        if ($user->id !== $me->id && $user->role_id) {
            abort_unless($this->assignableRoles($me->store_id)->contains('id', $user->role_id), 403);
        }
    }
}
