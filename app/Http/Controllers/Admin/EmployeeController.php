<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\IdentityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('branch', 'department')->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('job_title', 'like', "%{$s}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->paginate(25)->withQueryString();
        $branches  = Branch::orderBy('name')->get();
        $total     = Employee::count();

        return view('admin.employees.index', compact('employees', 'branches', 'total'));
    }

    public function show(Employee $employee)
    {
        $employee->load('branch', 'department', 'manager', 'activeAssets.device', 'assetAssignments.device', 'identityUser');

        $availableDevices = Device::where('status', 'available')
            ->orWhere(fn ($q) => $q->where('branch_id', $employee->branch_id)->where('status', 'available'))
            ->orderBy('name')
            ->get();

        return view('admin.employees.show', compact('employee', 'availableDevices'));
    }

    public function create()
    {
        $branches    = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $managers    = Employee::where('status', 'active')->orderBy('name')->get();
        $azureUsers  = IdentityUser::where('account_enabled', true)->orderBy('display_name')->get();

        return view('admin.employees.form', compact('branches', 'departments', 'managers', 'azureUsers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'azure_id'      => 'nullable|string|max:100',
            'branch_id'     => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'manager_id'    => 'nullable|exists:employees,id',
            'job_title'     => 'nullable|string|max:255',
            'status'        => 'required|in:active,terminated,on_leave',
            'hired_date'    => 'nullable|date',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $employee = Employee::create($validated);

        return redirect()
            ->route('admin.employees.show', $employee->id)
            ->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        $branches    = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $managers    = Employee::where('status', 'active')->where('id', '!=', $employee->id)->orderBy('name')->get();

        return view('admin.employees.form', compact('employee', 'branches', 'departments', 'managers'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'nullable|email|max:255',
            'azure_id'         => 'nullable|string|max:100',
            'branch_id'        => 'nullable|exists:branches,id',
            'department_id'    => 'nullable|exists:departments,id',
            'manager_id'       => 'nullable|exists:employees,id',
            'job_title'        => 'nullable|string|max:255',
            'status'           => 'required|in:active,terminated,on_leave',
            'hired_date'       => 'nullable|date',
            'terminated_date'  => 'nullable|date|after_or_equal:hired_date',
            'notes'            => 'nullable|string|max:2000',
        ]);

        $employee->update($validated);

        return redirect()
            ->route('admin.employees.show', $employee->id)
            ->with('success', 'Employee updated successfully.');
    }

    public function assignAsset(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'asset_id'      => 'required|exists:devices,id',
            'assigned_date' => 'required|date',
            'condition'     => 'required|in:good,fair,poor',
            'notes'         => 'nullable|string|max:500',
        ]);

        // Check not already assigned
        $existing = EmployeeAsset::where('asset_id', $validated['asset_id'])
            ->whereNull('returned_date')
            ->first();

        if ($existing) {
            return back()->with('error', 'This asset is already assigned to another employee.');
        }

        EmployeeAsset::create(array_merge($validated, ['employee_id' => $employee->id]));

        Device::where('id', $validated['asset_id'])->update(['status' => 'assigned']);

        return back()->with('success', 'Asset assigned successfully.');
    }

    public function returnAsset(Request $request, Employee $employee, EmployeeAsset $asset)
    {
        $request->validate([
            'returned_date' => 'required|date',
            'condition'     => 'required|in:good,fair,poor',
            'notes'         => 'nullable|string|max:500',
        ]);

        $asset->update([
            'returned_date' => $request->returned_date,
            'condition'     => $request->condition,
            'notes'         => $request->notes,
        ]);

        Device::where('id', $asset->asset_id)->update(['status' => 'available']);

        return back()->with('success', 'Asset returned successfully.');
    }
}
