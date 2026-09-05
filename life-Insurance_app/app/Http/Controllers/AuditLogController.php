<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => [
                'action' => (string) $request->query('action', ''),
                'user_id' => (string) $request->query('user_id', ''),
            ],
        ]);
    }
}
