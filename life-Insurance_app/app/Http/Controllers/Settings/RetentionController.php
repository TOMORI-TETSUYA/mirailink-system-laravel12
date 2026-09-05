<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRetentionRequest;
use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** 顧客情報の保存期間設定（仕様 6.11）。年数をソースコードへ固定しません。 */
final class RetentionController extends Controller
{
    public function edit(): View
    {
        return view('settings.retention.edit', [
            'retentionYears' => Setting::retentionYears(),
        ]);
    }

    public function update(UpdateRetentionRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $years = $request->validated('customer_retention_years');

        Setting::put(
            Setting::KEY_RETENTION_YEARS,
            $years === null ? null : (string) $years,
            $request->user()->id,
        );

        $auditLog->record(
            userId: $request->user()->id,
            action: 'setting.retention_updated',
            targetType: Setting::class,
            changedFields: [Setting::KEY_RETENTION_YEARS],
        );

        return redirect()->route('settings.retention.edit')->with('status', '保存期間の設定を更新しました。');
    }
}
