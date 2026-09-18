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

        return $this->stream($order->{$field}, download: $request->boolean('download'));
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

    private function stream(mixed $value, ?string $name = null, bool $download = false): Response
    {
        $path = $this->path($value);
        abort_if($path === null, 404);

        $headers = ['Cache-Control' => 'private, no-store, max-age=0'];
        $filename = $name ?: basename($path);

        foreach (['private', 'public'] as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            if ($disk === 'public' && ! Storage::disk('private')->exists($path)) {
                $stream = Storage::disk('public')->readStream($path);
                if (is_resource($stream)) {
                    Storage::disk('private')->writeStream($path, $stream);
                    fclose($stream);
                }
            }

            return $download
                ? Storage::disk($disk)->download($path, $filename, $headers)
                : Storage::disk($disk)->response($path, $filename, $headers);
        }

        abort(404);
    }

    private function path(mixed $value): ?string
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
        }

        if (is_array($value)) {
            $value = collect($value)->filter(fn ($item) => is_string($item) && $item !== '')->last();
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($value)), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return ($path === '' || str_contains($path, '..')) ? null : $path;
    }
}
