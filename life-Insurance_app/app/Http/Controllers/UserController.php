<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()->orderBy('login_id')->paginate(30),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request): User {
            $user = User::query()->create([
                'login_id' => $data['login_id'],
                'display_name' => $data['display_name'],
                'role' => $data['role'],
                'password' => $data['password'],
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'user.created',
                targetType: User::class,
                targetId: $user->id,
                changedFields: ['login_id', 'display_name', 'role'],
            );

            return $user;
        });

        return redirect()->route('users.index')->with('status', "ユーザー {$user->login_id} を作成しました。");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $isActive = (bool) $data['is_active'];

        if (! $isActive && ! $request->user()->can('deactivate', $user)) {
            return back()->withErrors(['is_active' => '自分自身のアカウントは停止できません。']);
        }

        DB::transaction(function () use ($user, $data, $isActive, $request): void {
            $user->fill([
                'display_name' => $data['display_name'],
                'role' => $data['role'],
                'is_active' => $isActive,
            ]);

            $changed = array_keys($user->getDirty());

            if (! empty($data['password'])) {
                $user->forceFill([
                    'password' => $data['password'],
                    'must_change_password' => true,
                ]);
                $changed[] = 'password';
            }

            $user->save();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'user.updated',
                targetType: User::class,
                targetId: $user->id,
                changedFields: $changed,
            );
        });

        return redirect()->route('users.index')->with('status', "ユーザー {$user->login_id} を更新しました。");
    }

    /** ユーザーは物理削除せず、アカウント停止（is_active=false）とします。 */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        DB::transaction(function () use ($user, $request): void {
            $user->forceFill(['is_active' => false])->save();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'user.deactivated',
                targetType: User::class,
                targetId: $user->id,
                changedFields: ['is_active'],
            );
        });

        return redirect()->route('users.index')->with('status', "ユーザー {$user->login_id} を停止しました。");
    }
}
