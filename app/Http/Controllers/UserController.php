<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $me      = auth()->user();
        $stores  = $me->is_super_admin ? Store::orderBy('name')->get() : collect();
        $storeId = $me->is_super_admin
            ? ($request->integer('store_id') ?: $stores->first()?->id)
            : $me->store_id;

        ActivityLog::trace('UserController@index', 'Listou utilizadores', ['store_id' => $storeId]);

        $users = User::with('role')
            ->where('store_id', $storeId)
            ->where('is_super_admin', false)
            ->orderBy('name')->get();

        return view('users.index', compact('stores', 'storeId', 'users'));
    }

    public function create(Request $request)
    {
        $storeId = $this->resolveStoreId($request);

        ActivityLog::trace('UserController@create', 'Abriu o formulário de novo utilizador', ['store_id' => $storeId]);

        return $this->form(new User(['store_id' => $storeId, 'is_active' => true]), $storeId);
    }

    public function store(Request $request)
    {
        $storeId = $this->resolveStoreId($request);

        ActivityLog::trace('UserController@store', 'A criar utilizador', ['store_id' => $storeId]);

        try {
            $data = $this->validated($request, $storeId);

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

            ActivityLog::record($user, 'created', "Utilizador «{$user->name}»", [
                'depois' => $user->only(['name', 'username', 'email', 'employee_code', 'role_id', 'is_active']),
            ]);
        } catch (ValidationException $e) {
            ActivityLog::trace('UserController@store', 'Validação falhou', ['erros' => $e->errors()], 'warning');
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            ActivityLog::trace('UserController@store', 'Erro ao criar utilizador', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

        return redirect()->route('users.index', ['store_id' => $storeId])->with('ok', 'Utilizador criado.');
    }

    public function edit(User $user)
    {
        $this->ensureAccess($user);

        ActivityLog::trace('UserController@edit', "Abriu a edição do utilizador «{$user->name}»", ['user_alvo' => $user->id]);

        return $this->form($user, $user->store_id);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAccess($user);

        ActivityLog::trace('UserController@update', "A actualizar utilizador «{$user->name}»", ['user_alvo' => $user->id]);

        try {
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

            $user->forceFill($fill);

            if ($user->isDirty()) {
                $props = ActivityLog::dirty($user);   // a senha aparece como ***
                $user->save();
                ActivityLog::record($user, 'updated', "Utilizador «{$user->name}»", $props);
            } else {
                ActivityLog::trace('UserController@update', 'Sem alterações', ['user_alvo' => $user->id]);
            }
        } catch (ValidationException $e) {
            ActivityLog::trace('UserController@update', 'Validação falhou', ['erros' => $e->errors()], 'warning');
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            ActivityLog::trace('UserController@update', 'Erro ao actualizar utilizador', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

        return redirect()->route('users.index', ['store_id' => $user->store_id])->with('ok', 'Utilizador actualizado.');
    }

    public function toggle(User $user)
    {
        $this->ensureAccess($user);

        ActivityLog::trace('UserController@toggle', "A mudar o estado de «{$user->name}»", ['user_alvo' => $user->id]);

        if ($user->id === auth()->id()) {
            ActivityLog::trace('UserController@toggle', '[blocked] Tentou desactivar a própria conta', [], 'warning');
            abort(403, 'Não podes desactivar a tua própria conta.');
        }

        try {
            $user->forceFill(['is_active' => !$user->is_active])->save();

            ActivityLog::record(
                $user,
                $user->is_active ? 'activated' : 'deactivated',
                "Utilizador «{$user->name}»"
            );
        } catch (\Throwable $e) {
            ActivityLog::trace('UserController@toggle', 'Erro ao mudar estado', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

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
            'username.unique'  => 'Este username já está em uso.',
            'email.unique'     => 'Este email já está em uso.',
            'role_id.in'       => 'Perfil inválido para esta loja.',
            'role_id.required' => 'Escolhe um perfil.',
        ]);
    }

    private function resolveStoreId(Request $request): int
    {
        $me = auth()->user();
        $id = $me->is_super_admin ? $request->integer('store_id') : $me->store_id;

        if (!($id && Store::whereKey($id)->exists())) {
            ActivityLog::trace('UserController@resolveStoreId', '[blocked] Loja inválida', ['store_id' => $id], 'warning');
            abort(404);
        }

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

        if ($user->is_super_admin && !$me->is_super_admin) {
            ActivityLog::trace('UserController@ensureAccess', '[blocked] Tentou aceder a um super admin', ['user_alvo' => $user->id], 'warning');
            abort(403);
        }

        if ($me->is_super_admin) {
            return;
        }

        if ($user->store_id !== $me->store_id) {
            ActivityLog::trace('UserController@ensureAccess', '[blocked] Utilizador de outra loja', ['user_alvo' => $user->id], 'warning');
            abort(403);
        }

        // o gerente não mexe em quem tem mais poder do que ele (excepto na própria conta)
        if ($user->id !== $me->id && $user->role_id
            && !$this->assignableRoles($me->store_id)->contains('id', $user->role_id)) {
            ActivityLog::trace('UserController@ensureAccess', '[blocked] Utilizador com mais poder', ['user_alvo' => $user->id], 'warning');
            abort(403);
        }
    }
}
