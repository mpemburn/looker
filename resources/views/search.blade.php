<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Search') }}
        </h2>
    </x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="p-2 mt-6 bg-white overflow-hidden shadow rounded">
            <div class="">
                <div class="p-2">
                    <form id="search_form">
                        @if (env('INSTALLED_TEST_DATABASES'))
                            <div>
                                <input type="radio" name="source" value="prod" checked> Production
                                <input type="radio" name="source" value="test"> Test
                            </div>
                            <hr>
                        @endif
                            <div class="form-parts">
                                <label for="type">Search in:
                                    <select id="type" name="type">
                                        <option value="posts">Posts</option>
                                        <option value="postmeta_values">Postmeta Values</option>
                                        <option value="postmeta_keys">Postmeta Keys</option>
                                        <option value="options">Option Values</option>
                                        <option value="option_name">Option Names</option>
                                        <option value="shortcodes">Shortcodes in Posts</option>
                                        <option value="plugins">Plugins</option>
                                        <option value="themes">Themes</option>
                                        <option value="blog_id">Blog by ID</option>
                                        <option value="updated">Most recent update</option>
                                    </select>
                                </label>
                                <label for="database">Database:
                                    <select name="database">
                                        @foreach($databases as $label => $dbName)
                                            <option value="{{ $dbName }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div id="search_section" class="form-parts">
                                <input id="search" type="search" name="text" placeholder="Enter search term"><br>
                                <input type="checkbox" name="exact"> Exact word match
                            </div>
                            <div class="form-parts">
                                <button id="search_btn" class="btn bg-blue-600 text-white hover:bg-blue-600 py-3 px-6 leading-tight" disabled>Search</button>
                                <img id="loading" class="hidden"
                                     src="https://cdnjs.cloudflare.com/ajax/libs/galleriffic/2.0.1/css/loader.gif" alt="" width="24"
                                     height="24">
                            </div>
                    </form>
                    <div id="found"></div>
                    <div id="results"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
