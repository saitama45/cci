<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $settings = [
            'audit_enabled' => Setting::get('audit_enabled', '1', $companyId) === '1',
            'audit_retention_months' => (int) Setting::get('audit_retention_months', '6', $companyId),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function pruneLogs()
    {
        Artisan::call('logs:prune');
        $output = Artisan::output();

        return redirect()->back()->with('success', 'Cleanup Finished: ' . $output);
    }

    public function updateAuditSettings(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'audit_enabled' => 'required|boolean',
            'audit_retention_months' => 'required|integer|in:6,12,36,60', // 6mo, 1yr, 3yr, 5yr
        ]);

        Setting::set('audit_enabled', $validated['audit_enabled'] ? '1' : '0', 'audit', $companyId);
        Setting::set('audit_retention_months', (string) $validated['audit_retention_months'], 'audit', $companyId);

        return redirect()->back()->with('success', 'Audit settings updated successfully.');
    }
}
