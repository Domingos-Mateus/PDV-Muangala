<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $stores  = $user->is_super_admin ? Store::orderBy('name')->get() : collect();
        $storeId = $user->is_super_admin
            ? ($request->integer('store_id') ?: $stores->first()?->id)
            : $user->store_id;

        ActivityLog::trace('RoleController@index', 'Listou perfis', ['store_id' => $storeId]);

        $roles = Role::where('store_id', $storeId)
            ->withCount(['users', 'permissions'])
            ->orderBy('name')->get();

        return view('roles.index', compact('stores', 'storeId', 'roles'));
    }

    public function create(Request $request)
    {
        $storeId = $this->resolveStoreId($request);

        ActivityLog::trace('RoleController@create', 'Abriu o formulário de novo perfil', ['store_id' => $storeId]);

        return $this->form(new Role(['store_id' => $storeId]), $storeId);
    }

    public function store(Request $request)
    {
        $storeId = $this->resolveStoreId($request);

        ActivityLog::trace('RoleController@store', 'A criar perfil', ['store_id' => $storeId]);

        try {
            $data = $this->validated($request, $storeId);

            $role = Role::create(['store_id' => $storeId, 'name' => $data['name']]);
            $role->permissions()->sync($this->allowedIds($data['permissions'] ?? []));

            ActivityLog::record($role, 'created', "Perfil «{$role->name}»", [
                'permissões' => $role->permissions()->pluck('key')->all(),
            ]);
        } catch (ValidationException $e) {
            ActivityLog::trace('RoleController@store', 'Validação falhou', ['erros' => $e->errors()], 'warning');
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            ActivityLog::trace('RoleController@store', 'Erro ao criar perfil', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

        return redirect()->route('roles.index', ['store_id' => $storeId])->with('ok', 'Perfil criado.');
    }

    public function edit(Role $role)
    {
        $this->ensureAccess($role);

        ActivityLog::trace('RoleController@edit', "Abriu a edição do perfil «{$role->name}»", ['role_id' => $role->id]);

        return $this->form($role, $role->store_id);
    }

    public function update(Request $request, Role $role)
    {
        $this->ensureAccess($role);

        ActivityLog::trace('RoleController@update', "A actualizar perfil «{$role->name}»", ['role_id' => $role->id]);

        try {
            $data = $this->validated($request, $role->store_id, $role);

            $oldName  = $role->name;
            $oldPerms = $role->permissions()->pluck('key')->sort()->values()->all();

            $role->update(['name' => $data['name']]);

            // o que o utilizador não pode gerir mantém-se como estava
            $allowed  = $this->allowedPermissions()->pluck('id');
            $kept     = $role->permissions()->pluck('permissions.id')->diff($allowed);
            $selected = collect($this->allowedIds($data['permissions'] ?? []));

            $role->permissions()->sync($kept->merge($selected)->unique()->values()->all());
            Cache::forget("perms.{$role->id}");

            $newPerms = $role->permissions()->pluck('key')->sort()->values()->all();

            if ($oldName !== $role->name || $oldPerms !== $newPerms) {
                ActivityLog::record($role, 'updated', "Perfil «{$role->name}»", [
                    'antes'     => ['nome' => $oldName],
                    'depois'    => ['nome' => $role->name],
                    'adicionou' => array_values(array_diff($newPerms, $oldPerms)),
                    'removeu'   => array_values(array_diff($oldPerms, $newPerms)),
                ]);
            } else {
                ActivityLog::trace('RoleController@update', 'Sem alterações', ['role_id' => $role->id]);
            }
        } catch (ValidationException $e) {
            ActivityLog::trace('RoleController@update', 'Validação falhou', ['erros' => $e->errors()], 'warning');
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            ActivityLog::trace('RoleController@update', 'Erro ao actualizar perfil', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

        return redirect()->route('roles.index', ['store_id' => $role->store_id])->with('ok', 'Perfil actualizado.');
    }

    public function destroy(Role $role)
    {
        $this->ensureAccess($role);

        ActivityLog::trace('RoleController@destroy', "A apagar perfil «{$role->name}»", ['role_id' => $role->id]);

        if ($role->users()->exists()) {
            ActivityLog::trace('RoleController@destroy', "[blocked] Perfil «{$role->name}» tem utilizadores", ['role_id' => $role->id], 'warning');
            return back()->withErrors('Este perfil tem utilizadores. Passa-os para outro perfil antes de apagar.');
        }

        try {
            $storeId = $role->store_id;
            Cache::forget("perms.{$role->id}");

            // o registo vai antes do delete(), para ainda ler as permissões
            ActivityLog::record($role, 'deleted', "Perfil «{$role->name}»", [
                'permissões' => $role->permissions()->pluck('key')->all(),
            ]);

            $role->delete();
        } catch (\Throwable $e) {
            ActivityLog::trace('RoleController@destroy', 'Erro ao apagar perfil', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

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

        if (!($id && Store::whereKey($id)->exists())) {
            ActivityLog::trace('RoleController@resolveStoreId', '[blocked] Loja inválida', ['store_id' => $id], 'warning');
            abort(404);
        }

        return $id;
    }

    private function ensureAccess(Role $role): void
    {
        $user = auth()->user();

        if (!($user->is_super_admin || $role->store_id === $user->store_id)) {
            ActivityLog::trace('RoleController@ensureAccess', "[blocked] Acesso negado ao perfil «{$role->name}»", ['role_id' => $role->id], 'warning');
            abort(403);
        }
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
