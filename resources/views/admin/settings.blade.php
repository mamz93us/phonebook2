<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
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

                    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Company Name -->
                        <div class="mb-6">
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Company Name
                            </label>
                            <input 
                                type="text" 
                                name="company_name" 
                                id="company_name"
                                value="{{ old('company_name', $settings->company_name) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            @error('company_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company Logo -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Company Logo
                            </label>

                            @if($settings->company_logo)
                                <div class="mb-4">
                                    <img 
                                        src="{{ asset('storage/' . $settings->company_logo) }}" 
                                        alt="Company Logo" 
                                        class="max-w-xs border border-gray-300 rounded p-2"
                                    >
                                    <form method="POST" action="{{ route('admin.settings.delete-logo') }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="text-red-600 text-sm hover:text-red-800"
                                            onclick="return confirm('Are you sure you want to delete the logo?')"
                                        >
                                            Delete Logo
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <input 
                                type="file" 
                                name="company_logo" 
                                id="company_logo"
                                accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            >
                            <p class="mt-1 text-sm text-gray-500">Recommended: PNG or JPG, max 2MB</p>
                            @error('company_logo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end">
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
