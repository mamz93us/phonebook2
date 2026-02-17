<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activity Logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.activity-logs') }}">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Model Type</label>
                                <select name="model_type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">All</option>
                                    <option value="Contact" {{ request('model_type') == 'Contact' ? 'selected' : '' }}>Contact</option>
                                    <option value="Branch" {{ request('model_type') == 'Branch' ? 'selected' : '' }}>Branch</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                                <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">All</option>
                                    <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                                    <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                                    <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Filter
                                </button>
                                <a href="{{ route('admin.activity-logs') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if($logs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($logs as $log)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->user->name ?? 'Unknown' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->model_type }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->model_id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $log->action == 'created' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $log->action == 'updated' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $log->action == 'deleted' ? 'bg-red-100 text-red-800' : '' }}">
                                                    {{ ucfirst($log->action) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                @if($log->action == 'created' || $log->action == 'deleted')
                                                    @php
                                                        $changes = is_array($log->changes) ? $log->changes : json_decode($log->changes, true);
                                                    @endphp
                                                    @if(isset($changes['first_name']))
                                                        {{ $changes['first_name'] }} {{ $changes['last_name'] ?? '' }}
                                                    @elseif(isset($changes['name']))
                                                        {{ $changes['name'] }}
                                                    @endif
                                                @elseif($log->action == 'updated')
                                                    @php
                                                        $changes = is_array($log->changes) ? $log->changes : json_decode($log->changes, true);
                                                        $new = $changes['new'] ?? [];
                                                    @endphp
                                                    {{ implode(', ', array_keys($new)) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $logs->withQueryString()->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No activity logs found.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
