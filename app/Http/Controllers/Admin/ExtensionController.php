<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UcmServer;
use App\Services\IppbxApiService;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    /**
     * Show extensions page with dropdown for UCM selection
     */
    public function index(Request $request)
    {
        $ucmServers  = UcmServer::active()->orderBy('name')->get();
        $extensions  = [];
        $error       = null;
        $selectedUcm = null;

        $ucmId = $request->get('ucm_id');

        if ($ucmId) {
            $selectedUcm = UcmServer::find($ucmId);

            if ($selectedUcm) {
                try {
                    $api        = new IppbxApiService($selectedUcm);
                    $extensions = $api->listExtensions();
                } catch (\Exception $e) {
                    $error = 'Could not connect to UCM: ' . $e->getMessage();
                }
            }
        } elseif ($ucmServers->count() === 1) {
            // Auto-select if only one UCM
            $selectedUcm = $ucmServers->first();
            try {
                $api        = new IppbxApiService($selectedUcm);
                $extensions = $api->listExtensions();
            } catch (\Exception $e) {
                $error = 'Could not connect to UCM: ' . $e->getMessage();
            }
        }

        return view('admin.extensions.index', compact(
            'ucmServers',
            'extensions',
            'selectedUcm',
            'error'
        ));
    }

    /**
     * Store a new extension on the selected UCM
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'ucm_id'        => 'required|exists:ucm_servers,id',
            'extension'     => 'required|string|max:20',
            'secret'        => 'required|string|min:6|max:100',
            'user_password' => 'required|string|min:6|max:100',
            'fullname'      => 'nullable|string|max:100',
            'permission'    => 'required|in:internal,local,national,international',
            'max_contacts'  => 'nullable|integer|min:1|max:10',
        ]);

        $ucm = UcmServer::findOrFail($data['ucm_id']);

        try {
            $api = new IppbxApiService($ucm);
            $api->createExtension([
                'extension'     => $data['extension'],
                'secret'        => $data['secret'],
                'user_password' => $data['user_password'],
                'fullname'      => $data['fullname'] ?? '',
                'permission'    => $data['permission'],
                'max_contacts'  => (string) ($data['max_contacts'] ?? 3),
            ]);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create extension: ' . $e->getMessage());
        }

        return redirect()->route('admin.extensions.index', ['ucm_id' => $data['ucm_id']])
            ->with('success', "Extension {$data['extension']} created successfully.");
    }

    /**
     * Update an existing extension on the UCM
     */
    public function update(Request $request, string $extension)
    {
        $data = $request->validate([
            'ucm_id'       => 'required|exists:ucm_servers,id',
            'fullname'     => 'nullable|string|max:100',
            'permission'   => 'required|in:internal,local,national,international',
            'max_contacts' => 'nullable|integer|min:1|max:10',
            'secret'       => 'nullable|string|min:6|max:100',
        ]);

        $ucm = UcmServer::findOrFail($data['ucm_id']);

        $updateData = [
            'permission'   => $data['permission'],
            'max_contacts' => (string) ($data['max_contacts'] ?? 3),
        ];

        if (!empty($data['fullname'])) {
            $updateData['fullname'] = $data['fullname'];
        }
        if (!empty($data['secret'])) {
            $updateData['secret'] = $data['secret'];
        }

        try {
            $api = new IppbxApiService($ucm);
            $api->updateExtension($extension, $updateData);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update extension: ' . $e->getMessage());
        }

        return redirect()->route('admin.extensions.index', ['ucm_id' => $data['ucm_id']])
            ->with('success', "Extension {$extension} updated successfully.");
    }

    /**
     * Delete an extension from the UCM
     */
    public function destroy(Request $request, string $extension)
    {
        $ucmId = $request->input('ucm_id');
        $ucm   = UcmServer::findOrFail($ucmId);

        try {
            $api = new IppbxApiService($ucm);
            $api->deleteExtension($extension);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete extension: ' . $e->getMessage());
        }

        return redirect()->route('admin.extensions.index', ['ucm_id' => $ucmId])
            ->with('success', "Extension {$extension} deleted successfully.");
    }
}
