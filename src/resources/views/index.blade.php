@extends(config('env-profiles.layout', 'env-profiles::layouts.default'))

@section('content')

<div id="env-profiles-app" class="min-h-screen bg-gray-50">
    <div class="container mx-auto py-8 px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Environment Profiles Manager</h1>
            <p class="mt-2 text-gray-600">Manage and switch between different .env configurations</p>
        </div>

        <env-profile-manager :initial-profiles='@json($profiles)' :initial-env-content='@json($currentEnv)'
            :default-app-name='@json($appName)'
            api-base-url="{{ url(config('env-profiles.api_prefix', 'api/env-profiles')) }}" />
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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://unpkg.com/alpinejs@3/dist/cdn.min.js" defer></script>
<script>
    require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
</script>
<script src="{{ asset('vendor/env-profiles/js/app.js') }}"></script>
@endpush
@endsection