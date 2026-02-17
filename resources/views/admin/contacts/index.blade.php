<!-- Add this button next to the search form in your existing admin/contacts/index.blade.php -->

<!-- In the top section where your search and create buttons are, add: -->
<div class="flex justify-between items-center mb-4">
    <div>
        <!-- Your existing search form -->
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.contacts.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Add Contact
        </a>
        <a href="{{ route('admin.contacts.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            Export to Excel
        </a>
    </div>
</div>

<!-- 
FULL UPDATED INDEX VIEW 
If you want to replace your entire contacts index view, use this:
-->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Contacts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Search and Actions -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <form method="GET" action="{{ route('admin.contacts.index') }}" class="flex gap-2 flex-1">
                            <input 
                                type="text" 
                                name="search" 
                                placeholder="Search contacts..."
                                value="{{ request('search') }}"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Search
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.contacts.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                    Clear
                                </a>
                            @endif
                        </form>

                        <div class="flex gap-2">
                            <a href="{{ route('admin.contacts.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                                Add Contact
                            </a>
                            <a href="{{ route('admin.contacts.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 whitespace-nowrap">
                                Export Excel
                            </a>
                        </div>
                    </div>

                    <!-- Table -->
                    @if($contacts->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($contacts as $contact)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $contact->first_name }} {{ $contact->last_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $contact->phone }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $contact->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $contact->branch->name ?? '' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('admin.contacts.edit', $contact) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                                <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $contacts->withQueryString()->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No contacts found.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
