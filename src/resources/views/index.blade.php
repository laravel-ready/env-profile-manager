@extends(config('env-profile-manager.layout', 'env-profile-manager::layouts.default'))

@section('content')

<div id="env-profile-manager-app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto py-8 px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Environment Profiles Manager</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Manage and switch between different .env configurations</p>
        </div>

        <env-profile-manager :initial-profiles='@json($profiles)' :initial-env-content='@json($currentEnv)'
            :default-app-name='@json($appName)'
            api-base-url="{{ url(config('env-profile-manager.api_prefix', 'api/env-profile-manager')) }}" />
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/editor/editor.main.css" rel="stylesheet">
<style>
    .monaco-editor-container {
        height: 500px;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
    }
    
    .dark .monaco-editor-container {
        border-color: #374151;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://unpkg.com/alpinejs@3/dist/cdn.min.js" defer></script>
<script>
    require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
</script>
<script type="module" src="{{ asset('vendor/env-profile-manager/js/app.js') }}"></script>
@endpush
@endsection