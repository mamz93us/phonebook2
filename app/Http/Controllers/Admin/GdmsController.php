<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UcmServer;
use App\Services\IppbxApiService;

class GdmsController extends Controller
{
    /**
     * Show live status for every configured UCM server by querying each
     * UCM's own HTTPS API directly (/api — challenge → login → getSystemStatus, listAccount).
     */
    public function ucmIndex()
    {
        $servers = UcmServer::active()->orderBy('name')->get();

        $results = [];

        foreach ($servers as $server) {
            $item = [
                'server'    => $server,
                'online'    => false,
                'error'     => null,
                'system'    => [],   // getSystemStatus response
                'general'   => [],   // getSystemGeneralStatus response
                'extensions'=> [],
                'summary'   => ['total' => 0, 'idle' => 0, 'inuse' => 0, 'unavailable' => 0, 'other' => 0],
            ];

            try {
                $api = new IppbxApiService($server);
                $api->login();

                $item['online']     = true;
                $item['system']     = $api->getSystemStatus();
                $item['general']    = $api->getSystemGeneralStatus();
                $item['extensions'] = $api->listExtensions(1, 1000);

                foreach ($item['extensions'] as $ext) {
                    $item['summary']['total']++;
                    $status = strtolower($ext['status'] ?? '');
                    if ($status === 'idle') {
                        $item['summary']['idle']++;
                    } elseif (in_array($status, ['inuse', 'busy', 'ringing'])) {
                        $item['summary']['inuse']++;
                    } elseif ($status === 'unavailable') {
                        $item['summary']['unavailable']++;
                    } else {
                        $item['summary']['other']++;
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
