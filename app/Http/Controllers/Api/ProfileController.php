<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profiles = $request->user()->profiles()->get();

        return response()->json(['profiles' => $profiles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'birth_time' => ['nullable', 'date_format:H:i:s'],
            'birth_place' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();
        $isFirst = $user->profiles()->count() === 0;

        $profile = $user->profiles()->create(array_merge($validated, [
            'is_default' => $isFirst,
        ]));

        return response()->json(['profile' => $profile], 201);
    }

    public function update(Request $request, Profile $profile): JsonResponse
    {
        if ($profile->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'date'],
            'birth_time' => ['nullable', 'date_format:H:i:s'],
            'birth_place' => ['sometimes', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $profile->update($validated);

        return response()->json(['profile' => $profile]);
    }

    public function destroy(Request $request, Profile $profile): JsonResponse
    {
        if ($profile->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        $user = $request->user();

        if ($user->profiles()->count() <= 1) {
            return response()->json(['message' => 'Нельзя удалить единственный профиль.'], 422);
        }

        $wasDefault = $profile->is_default;
        $profile->delete();

        if ($wasDefault) {
            $user->profiles()->first()?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Профиль удалён.']);
    }

    public function setDefault(Request $request, Profile $profile): JsonResponse
    {
        $user = $request->user();

        if ($profile->user_id !== $user->id) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        $user->profiles()->update(['is_default' => false]);
        $profile->update(['is_default' => true]);

        return response()->json(['profile' => $profile]);
    }
}
