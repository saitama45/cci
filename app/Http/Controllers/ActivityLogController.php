<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = ActivityLog::with(['user:id,name,email'])
            ->where('company_id', $companyId);

        // Filters
        if ($request->search) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        if ($request->module) {
            $query->where('module', $request->module);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest('created_at')
            ->paginate($request->per_page ?? 20)
            ->withQueryString();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'module', 'action', 'user_id', 'date_from', 'date_to']),
            'users' => User::where('company_id', $companyId)->select('id', 'name')->get(),
            'modules' => ActivityLog::where('company_id', $companyId)->distinct()->pluck('module'),
            'actions' => ActivityLog::where('company_id', $companyId)->distinct()->pluck('action'),
        ]);
    }
}
