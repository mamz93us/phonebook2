<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UcmServer;
use App\Services\IppbxApiService;

class GdmsController extends Controller
{
    /**
     * Show live status for every configured UCM server by querying each
     * UCM's own HTTPS API directly (/api — challenge → login → status queries).
     */
    public function ucmIndex()
    {
        $servers = UcmServer::active()->orderBy('name')->get();

        $results = [];

        foreach ($servers as $server) {
            $item = [
                'server'     => $server,
                'online'     => false,
                'error'      => null,
                'system'     => [],
                'general'    => [],
                'mac'        => null,
                'extensions' => [],
                'trunks'     => [],
                'summary'    => ['total' => 0, 'idle' => 0, 'inuse' => 0, 'unavailable' => 0, 'other' => 0],
                'trunk_summary' => ['total' => 0, 'reachable' => 0, 'unreachable' => 0],
            ];

            try {
                $api = new IppbxApiService($server);
                $api->login();

                $item['online']     = true;
                $item['system']     = $api->getSystemStatus();
                $item['general']    = $api->getSystemGeneralStatus();

                // Format the uptime with days
                if (!empty($item['system']['up-time'])) {
                    $item['system']['up-time-formatted'] = IppbxApiService::formatUptime($item['system']['up-time']);
                }

                // MAC address
                $network = $api->getNetworkStatus();
                $item['mac'] = IppbxApiService::extractMac($network);

                // Extensions
                $item['extensions'] = $api->listExtensions(1, 1000);
                foreach ($item['extensions'] as $ext) {
                    $item['summary']['total']++;
                    $status = strtolower($ext['status'] ?? '');
                    if ($status === 'idle')                                        $item['summary']['idle']++;
                    elseif (in_array($status, ['inuse', 'busy', 'ringing']))       $item['summary']['inuse']++;
                    elseif ($status === 'unavailable')                             $item['summary']['unavailable']++;
                    else                                                           $item['summary']['other']++;
                }

                // Trunks
                $item['trunks'] = $api->listVoIPTrunks();
                foreach ($item['trunks'] as $trunk) {
                    $item['trunk_summary']['total']++;
                    $ts = strtolower($trunk['status'] ?? '');
                    if (str_contains($ts, 'unreachable')) {
                        $item['trunk_summary']['unreachable']++;
                    } else {
                        $item['trunk_summary']['reachable']++;
                    }
                }

            } catch (\Exception $e) {
                $item['error'] = $e->getMessage();
            }

            $results[] = $item;
        }

        return view('admin.gdms.ucm', compact('results'));
    }
}
