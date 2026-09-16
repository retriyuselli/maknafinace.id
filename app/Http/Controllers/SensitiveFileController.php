<?php

namespace App\Http\Controllers;

use App\Models\DocumentAttachment;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SensitiveFileController extends Controller
{
    public function order(Request $request, Order $order, string $field): Response
    {
        abort_unless(in_array($field, ['doc_kontrak', 'agreement_product'], true), 404);
        Gate::authorize('view', $order);

        return $this->stream((string) $order->{$field});
    }

    public function leave(Request $request, LeaveRequest $leaveRequest, int $index): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->id === $leaveRequest->user_id || Gate::forUser($user)->allows('view', $leaveRequest)), 403);

        $documents = (array) $leaveRequest->documents;
        abort_unless(isset($documents[$index]) && is_string($documents[$index]), 404);

        return $this->stream($documents[$index]);
    }

    public function signature(Request $request, User $user): Response
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->is($user) || Gate::forUser($actor)->allows('view', $user)), 403);

        return $this->stream((string) $user->signature_url, 'signature.png');
    }

    public function documentAttachment(DocumentAttachment $attachment): Response
    {
        $attachment->loadMissing('document');
        Gate::authorize('view', $attachment->document);

        return $this->stream((string) $attachment->file_path, $attachment->file_name);
    }

    private function stream(string $path, ?string $name = null): Response
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        abort_if($path === '' || str_contains($path, '..'), 404);

        foreach (['private', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response(
                    $path,
                    $name ?: basename($path),
                    ['Cache-Control' => 'private, no-store, max-age=0'],
                );
            }
        }

        abort(404);
    }
}
