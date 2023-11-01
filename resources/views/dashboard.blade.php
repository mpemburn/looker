<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <strong>WordPress Looker</strong> is a tool for searching one or more databases on the following items:
                    <ul class="linked list-disc list-inside">
                        <li data-value="posts">Posts</li>
                        <li data-value="postmeta_values">Postmeta Values</li>
                        <li data-value="postmeta_keys">Postmeta Keys</li>
                        <li data-value="options">Option Values</li>
                        <li data-value="option_name">Option Names</li>
                        <li data-value="users">Users</li>
                        <li data-value="shortcodes">Shortcodes in Posts</li>
                        <li data-value="plugins">Plugins</li>
                        <li data-value="themes">Themes</li>
                        <li data-value="blog_id">Blog by ID</li>
                        <li data-value="list_all">List all blogs</li>
                        <li data-value="updated">Most recent update</li>
                    </ul>
                    <br>
                    <strong>Available databases:</strong>
                    <ul>
                        @foreach($production as $site => $dbName)
                            <li><strong>{{ $dbName }}</strong> <a href="https://{{$site}}" target="_blank">(https://{{$site}})</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
