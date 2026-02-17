<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;

class PublicContactController extends Controller
{
    /**
     * Display public phonebook page
     */
    public function index(Request $request)
    {
        $query = Contact::with('branch')->orderBy('first_name');

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%" . $request->search . "%")
                  ->orWhere('last_name', 'like', "%" . $request->search . "%")
                  ->orWhere('phone', 'like', "%" . $request->search . "%")
                  ->orWhere('email', 'like', "%" . $request->search . "%");
            });
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id !== '') {
            $query->where('branch_id', $request->branch_id);
        }

        $contacts = $query->paginate(50);
        $branches = Branch::orderBy('name')->get();
        $settings = Setting::get();

        return view('contacts.index', compact('contacts', 'branches', 'settings'));

    }
}
