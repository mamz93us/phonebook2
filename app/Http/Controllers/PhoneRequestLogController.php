<?php

namespace App\Http\Controllers;

use App\Models\PhoneRequestLog;
use Illuminate\Support\Facades\DB;

class PhoneRequestLogController extends Controller
{
    public function index()
    {
        $logs = PhoneRequestLog::select(
                'mac',
                'model',
                DB::raw('MAX(created_at) as last_request_at'),
                DB::raw('COUNT(*) as total_requests')
            )
            ->whereNotNull('mac')
            ->groupBy('mac', 'model')
            ->orderByDesc('last_request_at')
            ->get(); // [web:190][web:192]

        return view('admin.phone-logs.index', compact('logs'));
    }
}
