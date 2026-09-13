<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['roles']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before:'.now()->subYears(17)->toDateString()],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'department' => ['nullable', 'string', 'in:bisnis,operasional'],
            'emergency_contact' => ['nullable', 'string', 'max:1000'],
        ]);

        if (array_key_exists('email', $data) && $data['email'] !== $user->email && ! $user->hasRole('super_admin')) {
            unset($data['email']);
        }

        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => new UserResource($user->fresh()->loadMissing(['roles'])),
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_url = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar berhasil diperbarui.',
            'data' => new UserResource($user->fresh()->loadMissing(['roles'])),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->password = $request->password;
        $user->save();

        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $user->tokens()
            ->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))
            ->delete();

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentId = $user->currentAccessToken()?->id;

        $devices = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->map(static function ($token) use ($currentId): array {
                return [
                    'id' => (int) $token->id,
                    'name' => $token->name,
                    'last_used_at' => optional($token->last_used_at)?->toIso8601String(),
                    'created_at' => optional($token->created_at)?->toIso8601String(),
                    'is_current' => $currentId !== null && (int) $token->id === (int) $currentId,
                ];
            })
            ->values();

        return response()->json([
            'data' => $devices,
        ]);
    }
}
