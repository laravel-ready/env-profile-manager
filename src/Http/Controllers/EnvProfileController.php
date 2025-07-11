<?php

namespace LaravelReady\EnvProfiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelReady\EnvProfiles\Http\Requests\StoreEnvProfileRequest;
use LaravelReady\EnvProfiles\Http\Requests\UpdateEnvProfileRequest;
use LaravelReady\EnvProfiles\Models\EnvProfile;
use LaravelReady\EnvProfiles\Services\EnvFileService;

class EnvProfileController extends Controller
{
    protected $envFileService;

    public function __construct(EnvFileService $envFileService)
    {
        $this->envFileService = $envFileService;
    }

    public function index()
    {
        $profiles = EnvProfile::orderBy('name')->get();
        $currentEnv = $this->envFileService->read();
        $appName = config('app.name', 'Laravel');
        
        return view('env-profiles::index', compact('profiles', 'currentEnv', 'appName'));
    }

    public function apiIndex()
    {
        return response()->json([
            'profiles' => EnvProfile::orderBy('name')->get(),
            'current_env' => $this->envFileService->read(),
            'app_name' => config('app.name', 'Laravel'),
        ]);
    }

    public function store(StoreEnvProfileRequest $request)
    {
        $profile = EnvProfile::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profile created successfully',
                'profile' => $profile,
            ], 201);
        }

        return redirect()->route('env-profiles.index')
            ->with('success', 'Profile created successfully');
    }

    public function show(EnvProfile $profile)
    {
        return response()->json($profile);
    }

    public function update(UpdateEnvProfileRequest $request, EnvProfile $profile)
    {
        $profile->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profile updated successfully',
                'profile' => $profile,
            ]);
        }

        return redirect()->route('env-profiles.index')
            ->with('success', 'Profile updated successfully');
    }

    public function destroy(Request $request, EnvProfile $profile)
    {
        $profile->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profile deleted successfully',
            ]);
        }

        return redirect()->route('env-profiles.index')
            ->with('success', 'Profile deleted successfully');
    }

    public function activate(Request $request, EnvProfile $profile)
    {
        $profile->activate();
        $this->envFileService->write($profile->content);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profile activated and applied successfully',
                'profile' => $profile,
            ]);
        }

        return redirect()->route('env-profiles.index')
            ->with('success', 'Profile activated and applied successfully');
    }

    public function getCurrentEnv()
    {
        return response()->json([
            'content' => $this->envFileService->read(),
        ]);
    }

    public function updateCurrentEnv(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $this->envFileService->write($request->input('content'));

        return response()->json([
            'message' => '.env file updated successfully',
        ]);
    }
}