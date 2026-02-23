<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GdmsService;

class GdmsController extends Controller
{
    /**
     * Show all UCM devices from GDMS with online/offline status.
     */
    public function ucmIndex()
    {
        $devices = null;
        $error   = null;

        try {
            $gdms    = new GdmsService();
            $devices = $gdms->listDevices(productName: 'UCM');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.gdms.ucm', compact('devices', 'error'));
    }
}
