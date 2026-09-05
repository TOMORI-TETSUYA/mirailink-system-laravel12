<?php

namespace App\View\Composers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerIntention;
use App\Models\InsuranceContract;
use App\Models\Interaction;
use Illuminate\View\View;

/** ダッシュボード（index.blade.php）の表示内容を組み立てます（仕様 7.2）。 */
final class DashboardComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $visible = Customer::query()->visibleTo($user);

        $customerCount = (clone $visible)->count();
        $monthlyCustomerCount = (clone $visible)->where('created_at', '>=', now()->startOfMonth())->count();

        $confirmedCustomerIds = CustomerIntention::query()
            ->whereNotNull('confirmed_at')
            ->select('customer_id');
        $pendingIntentionCount = (clone $visible)->whereNotIn('id', $confirmedCustomerIds)->count();

        $renewalContractCount = InsuranceContract::query()
            ->whereIn('customer_id', (clone $visible)->select('id'))
            ->whereBetween('maturity_date', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->count();

        $recentInteractions = Interaction::query()
            ->with(['customer', 'user'])
            ->whereIn('customer_id', (clone $visible)->select('id'))
            ->orderByDesc('contacted_at')
            ->limit(5)
            ->get();

        $myCustomers = Customer::query()
            ->where('assigned_user_id', $user->id)
            ->with('latestInteraction')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $securityNotices = [];

        $failedLogins = AuditLog::query()
            ->where('action', 'auth.login_failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($failedLogins > 0) {
            $securityNotices[] = "過去24時間にログイン失敗が {$failedLogins} 件あります。";
        }

        if ($user->isAdmin() && $user->last_login_at === null) {
            $securityNotices[] = '管理者アカウントには本番導入前に多要素認証の追加が必要です。';
        }

        $view->with(compact(
            'customerCount',
            'monthlyCustomerCount',
            'pendingIntentionCount',
            'renewalContractCount',
            'recentInteractions',
            'myCustomers',
            'securityNotices',
        ));
    }
}
