<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogActivity
{
    /**
     * Log a system activity
     */
    public static function log($module, $action, $description, $record = null)
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? null;

        // Check database setting first, fallback to config/env
        $isEnabled = \App\Models\Setting::get('audit_enabled', config('audit.enabled', '1'), $companyId);
        
        if ($isEnabled === '0' || $isEnabled === false || $isEnabled === 'false') {
            return null;
        }

        return ActivityLog::create([
            'company_id' => $user->company_id ?? null,
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'record_id' => $record ? $record->id : null,
            'record_type' => $record ? get_class($record) : null,
            'ip_address' => Request::ip(),
        ]);
    }
}
