<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\PhoneRequestLog;
use App\Models\Setting;
use App\Models\UcmServer;
use App\Services\IppbxApiService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Simple DB counts (instant) ────────────────────────────────────
        $branchCount       = Branch::count();
        $contactCount      = Contact::count();
        $phoneRequestCount = PhoneRequestLog::distinct('mac')->whereNotNull('mac')->count();
        $totalXmlRequests  = PhoneRequestLog::count();

        // ── UCM live stats (cached 5 min per server) ──────────────────────
        $ucmServers = UcmServer::orderBy('name')->get();
        $ucmStats   = [];

        foreach ($ucmServers as $server) {
            $cacheKey = "dashboard_ucm_{$server->id}_" . md5($server->url . $server->api_username);

            if (!$server->is_active) {
                $ucmStats[] = [
                    'server' => $server,
                    'stats'  => ['online' => false, 'error' => 'Server is disabled'],
                ];
                continue;
            }

            $stats = Cache::remember($cacheKey, 300, function () use ($server) {
                try {
                    $api = new IppbxApiService($server);
                    $api->login();

                    $system     = $api->getSystemStatus();
                    $general    = $api->getSystemGeneralStatus();
                    $extensions = $api->listExtensions(1, 2000);
                    $trunks     = $api->listVoIPTrunks();

                    $extCounts = [
                        'total'       => count($extensions),
                        'idle'        => 0,
                        'inuse'       => 0,
                        'unavailable' => 0,
                    ];
                    foreach ($extensions as $ext) {
                        $s = strtolower($ext['status'] ?? '');
                        if ($s === 'idle')                                     $extCounts['idle']++;
                        elseif (in_array($s, ['inuse', 'busy', 'ringing']))    $extCounts['inuse']++;
                        elseif ($s === 'unavailable')                          $extCounts['unavailable']++;
                    }

                    return [
                        'online'    => true,
                        'model'     => $general['product-model'] ?? 'UCM',
                        'firmware'  => $general['prog-version']  ?? '-',
                        'serial'    => $system['serial-number']  ?? '-',
                        'uptime'    => $system['up-time']        ?? '-',
                        'extensions'=> $extCounts,
                        'trunks'    => count($trunks),
                    ];
                } catch (\Exception $e) {
                    return ['online' => false, 'error' => $e->getMessage()];
                }
            });

            $ucmStats[] = ['server' => $server, 'stats' => $stats];
        }

        // ── Aggregate UCM totals ───────────────────────────────────────────
        $ucmOnline     = collect($ucmStats)->where('stats.online', true)->count();
        $totalExt      = collect($ucmStats)->sum(fn ($u) => $u['stats']['extensions']['total'] ?? 0);
        $totalTrunks   = collect($ucmStats)->sum(fn ($u) => $u['stats']['trunks'] ?? 0);
        $totalIdle     = collect($ucmStats)->sum(fn ($u) => $u['stats']['extensions']['idle']        ?? 0);
        $totalInUse    = collect($ucmStats)->sum(fn ($u) => $u['stats']['extensions']['inuse']       ?? 0);
        $totalUnavail  = collect($ucmStats)->sum(fn ($u) => $u['stats']['extensions']['unavailable'] ?? 0);

        $settings = Setting::get();

        return view('admin.dashboard', compact(
            'branchCount',
            'contactCount',
            'phoneRequestCount',
            'totalXmlRequests',
            'ucmStats',
            'ucmOnline',
            'totalExt',
            'totalTrunks',
            'totalIdle',
            'totalInUse',
            'totalUnavail',
            'settings'
        ));
    }
}
