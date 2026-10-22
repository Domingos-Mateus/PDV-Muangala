<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $stores = $user->is_super_admin ? Store::orderBy('name')->get() : collect();
        $storeId = $user->is_super_admin
            ? ($request->integer('store_id') ?: $stores->first()?->id)
            : $user->store_id;

        $roles = Role::where('store_id', $storeId)
            ->withCount(['users', 'permissions'])
            ->orderBy('name')->get();

        return view('roles.index', compact('stores', 'storeId', 'roles'));
    }

    public function create(Request $request)
    {
        $storeId = $this->resolveStoreId($request);
        return $this->form(new Role(['store_id' => $storeId]), $storeId);
    }

    public function store(Request $request)
    {
        $storeId = $this->resolveStoreId($request);
        $data    = $this->validated($request, $storeId);

        $role = Role::create(['store_id' => $storeId, 'name' => $data['name']]);
        $role->permissions()->sync($this->allowedIds($data['permissions'] ?? []));

        return redirect()->route('roles.index', ['store_id' => $storeId])->with('ok', 'Perfil criado.');
    }

    public function edit(Role $role)
    {
        $this->ensureAccess($role);
        return $this->form($role, $role->store_id);
    }

    public function update(Request $request, Role $role)
    {
        $this->ensureAccess($role);
        $data = $this->validated($request, $role->store_id, $role);

        $role->update(['name' => $data['name']]);

        // o que o utilizador não pode gerir mantém-se como estava
        $allowed  = $this->allowedPermissions()->pluck('id');
        $kept     = $role->permissions()->pluck('permissions.id')->diff($allowed);
        $selected = collect($this->allowedIds($data['permissions'] ?? []));

        $role->permissions()->sync($kept->merge($selected)->unique()->values()->all());
        Cache::forget("perms.{$role->id}");

        return redirect()->route('roles.index', ['store_id' => $role->store_id])->with('ok', 'Perfil actualizado.');
    }

    public function destroy(Role $role)
    {
        $this->ensureAccess($role);

        if ($role->users()->exists()) {
            return back()->withErrors('Este perfil tem utilizadores. Passa-os para outro perfil antes de apagar.');
        }

        $storeId = $role->store_id;
        Cache::forget("perms.{$role->id}");
        $role->delete();

        return redirect()->route('roles.index', ['store_id' => $storeId])->with('ok', 'Perfil apagado.');
    }

    // ---------- auxiliares ----------

    private function form(Role $role, ?int $storeId)
    {
        return view('roles.form', [
            'role'     => $role,
            'storeId'  => $storeId,
            'store'    => Store::find($storeId),
            'groups'   => Permission::orderBy('id')->get()->groupBy('group'),
            'selected' => $role->exists ? $role->permissions()->pluck('permissions.id')->all() : [],
            'allowed'  => $this->allowedPermissions()->pluck('id')->all(),
        ]);
    }

    private function validated(Request $request, ?int $storeId, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('roles', 'name')->where('store_id', $storeId)->ignore($role?->id),
            ],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], ['name.unique' => 'Esta loja já tem um perfil com esse nome.']);
    }

    private function resolveStoreId(Request $request): int
    {
        $user = auth()->user();
        $id = $user->is_super_admin ? $request->integer('store_id') : $user->store_id;
        abort_unless($id && Store::whereKey($id)->exists(), 404);
        return $id;
    }

    private function ensureAccess(Role $role): void
    {
        $user = auth()->user();
        abort_unless($user->is_super_admin || $role->store_id === $user->store_id, 403);
    }

    private function allowedPermissions()
    {
        $user = auth()->user();
        if ($user->is_super_admin) {
            return Permission::all();
        }
        $keys = $user->role?->permissions()->pluck('key') ?? collect();
        return Permission::whereIn('key', $keys)->get();
    }

    private function allowedIds(array $ids): array
    {
        return array_values(array_intersect($ids, $this->allowedPermissions()->pluck('id')->all()));
    }
}
