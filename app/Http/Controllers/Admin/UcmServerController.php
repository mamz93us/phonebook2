<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UcmServer;
use Illuminate\Http\Request;

class UcmServerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'url'          => 'required|url|max:255',
            'api_username' => 'required|string|max:100',
            'api_password' => 'required|string|max:255',
        ]);

        UcmServer::create($data);

        return redirect()->route('admin.settings.index')
            ->with('success', 'UCM Server added successfully.');
    }

    public function update(Request $request, UcmServer $ucmServer)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'url'          => 'required|url|max:255',
            'api_username' => 'required|string|max:100',
            'api_password' => 'nullable|string|max:255',
        ]);

        // Keep old password if field is empty
        if (empty($data['api_password'])) {
            unset($data['api_password']);
        }

        $ucmServer->update($data);

        return redirect()->route('admin.settings.index')
            ->with('success', 'UCM Server updated successfully.');
    }

    public function destroy(UcmServer $ucmServer)
    {
        $ucmServer->delete();

        return redirect()->route('admin.settings.index')
            ->with('success', 'UCM Server deleted.');
    }

    public function toggleActive(UcmServer $ucmServer)
    {
        $ucmServer->update(['is_active' => !$ucmServer->is_active]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'UCM Server status updated.');
    }
}
