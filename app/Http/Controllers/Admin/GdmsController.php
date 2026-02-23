<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GdmsService;

class GdmsController extends Controller
{
    /**
     * Show all UCM / On-Premise PBX devices from GDMS with online/offline status.
     */
    public function ucmIndex()
    {
        $devices  = null;
        $error    = null;
        $rawDebug = null;   // shown only when devices = 0, helps diagnose structure

        try {
            $gdms    = new GdmsService();
            $devices = $gdms->listOnPremisePbx();

            // If nothing came back, also fetch raw device list for debug display
            if (count($devices) === 0) {
                try {
                    $allDevices = $gdms->listDevices();   // no filter
                    $rawDebug   = $allDevices;
                } catch (\Exception) {
                    // not critical
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.gdms.ucm', compact('devices', 'error', 'rawDebug'));
    }
}
