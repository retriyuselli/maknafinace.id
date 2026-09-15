<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FinanceProjectWriteService;
use App\Services\FinanceSummaryService;
use App\Support\CompanySubscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceSummaryService $finance,
        private readonly FinanceProjectWriteService $projectWrites,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);

        return response()->json([
            'data' => $this->finance->dashboard($period['from'], $period['to']),
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json(
            $this->finance->projects(
                $data['status'] ?? null,
                (int) ($data['per_page'] ?? 20),
            )
        );
    }

    public function projectsOverview(): JsonResponse
    {
        return response()->json([
            'data' => $this->finance->orderOverviewStats(),
        ]);
    }

    public function projectOptions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->projectWrites->options(
                $user,
                $request->filled('order_id') ? (int) $request->integer('order_id') : null,
            ),
        ]);
    }

    public function projectStore(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! CompanySubscription::canCreate(CompanySubscription::RESOURCE_ORDERS)) {
            return response()->json([
                'message' => CompanySubscription::fullMessage(CompanySubscription::RESOURCE_ORDERS),
            ], 403);
        }

        foreach (['items', 'payments'] as $key) {
            $value = $request->input($key);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $request->merge([$key => is_array($decoded) ? $decoded : []]);
            }
        }

        if ($request->input('note') === '') {
            $request->merge(['note' => null]);
        }

        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:32'],
            'prospect_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'employee_id' => ['required', 'integer'],
            'no_kontrak' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:pending,processing,done,cancelled'],
            'note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payments' => ['nullable', 'array'],
            'payments.*.keterangan' => ['required', 'string', 'max:255'],
            'payments.*.payment_method_id' => ['required', 'integer'],
            'payments.*.nominal' => ['required'],
            'payments.*.kategori_transaksi' => ['required', 'in:uang_masuk,uang_keluar'],
            'payments.*.tgl_bayar' => ['required', 'date'],
            'doc_kontrak' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'agreement_product' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'prospect_id.required' => 'Prospek wajib dipilih.',
            'user_id.required' => 'Account Manager wajib dipilih.',
            'employee_id.required' => 'Event Manager wajib dipilih.',
            'no_kontrak.required' => 'Nomor kontrak wajib diisi.',
            'pax.required' => 'Pax wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
            'items.required' => 'Minimal satu paket harus dipilih.',
            'doc_kontrak.required' => 'File kontrak PDF wajib diunggah.',
            'doc_kontrak.mimes' => 'Kontrak harus berupa PDF.',
            'agreement_product.required' => 'File persetujuan produk PDF wajib diunggah.',
            'agreement_product.mimes' => 'Persetujuan produk harus berupa PDF.',
        ]);

        $paymentProofs = [];
        foreach ($request->allFiles() as $key => $file) {
            if (! is_string($key) || ! preg_match('/^payment_proof_(\d+)$/', $key, $matches)) {
                continue;
            }
            $request->validate([
                $key => ['file', 'mimes:jpeg,jpg,png', 'max:1280'],
            ], [
                "$key.mimes" => 'Bukti bayar harus JPG atau PNG.',
                "$key.max" => 'Bukti bayar maksimal 1MB.',
            ]);
            $paymentProofs[(int) $matches[1]] = $file;
        }

        return response()->json([
            'message' => 'Proyek berhasil ditambahkan.',
            'data' => $this->projectWrites->store($user, $data, [
                'doc_kontrak' => $request->file('doc_kontrak'),
                'agreement_product' => $request->file('agreement_product'),
            ], $paymentProofs),
        ], 201);
    }

    public function projectUpdate(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $order = $this->finance->scopedOrdersQuery($user)->find($id);

        if (! $order) {
            return response()->json(['message' => 'Proyek tidak ditemukan.'], 404);
        }

        if (! $this->projectWrites->canEdit($user, $order)) {
            return response()->json([
                'message' => 'Proyek sudah selesai. Hanya Super Admin yang dapat mengedit.',
            ], 403);
        }

        foreach (['items', 'payments'] as $key) {
            $value = $request->input($key);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $request->merge([$key => is_array($decoded) ? $decoded : []]);
            }
        }

        if ($request->input('note') === '') {
            $request->merge(['note' => null]);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'employee_id' => ['required', 'integer'],
            'no_kontrak' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:pending,processing,done,cancelled'],
            'note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payments' => ['nullable', 'array'],
            'payments.*.id' => ['nullable', 'integer'],
            'payments.*.keterangan' => ['required', 'string', 'max:255'],
            'payments.*.payment_method_id' => ['required', 'integer'],
            'payments.*.nominal' => ['required'],
            'payments.*.kategori_transaksi' => ['required', 'in:uang_masuk,uang_keluar'],
            'payments.*.tgl_bayar' => ['required', 'date'],
            'doc_kontrak' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'agreement_product' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'user_id.required' => 'Account Manager wajib dipilih.',
            'employee_id.required' => 'Event Manager wajib dipilih.',
            'no_kontrak.required' => 'Nomor kontrak wajib diisi.',
            'items.required' => 'Minimal satu paket harus dipilih.',
        ]);

        $paymentProofs = [];
        foreach ($request->allFiles() as $key => $file) {
            if (! is_string($key) || ! preg_match('/^payment_proof_(\d+)$/', $key, $matches)) {
                continue;
            }
            $request->validate([
                $key => ['file', 'mimes:jpeg,jpg,png', 'max:1280'],
            ], [
                "$key.mimes" => 'Bukti bayar harus JPG atau PNG.',
                "$key.max" => 'Bukti bayar maksimal 1MB.',
            ]);
            $paymentProofs[(int) $matches[1]] = $file;
        }

        $files = [];
        if ($request->hasFile('doc_kontrak')) {
            $files['doc_kontrak'] = $request->file('doc_kontrak');
        }
        if ($request->hasFile('agreement_product')) {
            $files['agreement_product'] = $request->file('agreement_product');
        }

        return response()->json([
            'message' => 'Proyek berhasil diperbarui.',
            'data' => $this->projectWrites->update($user, $order, $data, $files, $paymentProofs),
        ]);
    }

    public function prospects(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json(
            $this->finance->prospects(
                $data['status'] ?? null,
                (int) ($data['per_page'] ?? 20),
            )
        );
    }

    public function prospectShow(int $id): JsonResponse
    {
        $detail = $this->finance->prospectDetail($id);

        if (! $detail) {
            return response()->json(['message' => 'Prospek tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }

    public function prospectStore(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! CompanySubscription::canCreate(CompanySubscription::RESOURCE_PROSPECTS)) {
            return response()->json([
                'message' => CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PROSPECTS),
            ], 403);
        }

        return response()->json([
            'message' => 'Prospek berhasil ditambahkan.',
            'data' => $this->finance->storeProspect($user, $this->validatedProspectPayload($request)),
        ], 201);
    }

    public function prospectUpdate(Request $request, int $id): JsonResponse
    {
        $detail = $this->finance->updateProspect($id, $this->validatedProspectPayload($request));

        if (! $detail) {
            return response()->json(['message' => 'Prospek tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Prospek berhasil diperbarui.',
            'data' => $detail,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProspectPayload(Request $request): array
    {
        foreach (['date_lamaran', 'date_akad', 'date_resepsi', 'time_lamaran', 'time_akad', 'time_resepsi', 'notes'] as $key) {
            if ($request->input($key) === '') {
                $request->merge([$key => null]);
            }
        }

        $request->merge([
            'phone' => FinanceSummaryService::normalizeProspectPhone((string) $request->input('phone', '')),
            'total_penawaran' => (int) preg_replace('/\D+/', '', (string) $request->input('total_penawaran', '0')),
            'notes' => trim((string) $request->input('notes', '')) ?: 'Tidak ada catatan',
        ]);

        return $request->validate([
            'name_event' => ['required', 'string', 'max:255'],
            'name_cpp' => ['required', 'string', 'max:255'],
            'name_cpw' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^[0-9]{8,15}$/'],
            'address' => ['required', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'total_penawaran' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'date_lamaran' => ['nullable', 'date'],
            'date_akad' => ['nullable', 'date'],
            'date_resepsi' => ['nullable', 'date'],
            'time_lamaran' => ['nullable', 'date_format:H:i'],
            'time_akad' => ['nullable', 'date_format:H:i'],
            'time_resepsi' => ['nullable', 'date_format:H:i'],
        ], [
            'name_event.required' => 'Nama acara wajib diisi.',
            'name_cpp.required' => 'Nama calon pengantin pria wajib diisi.',
            'name_cpw.required' => 'Nama calon pengantin wanita wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon 8–15 digit, tanpa 0 di depan.',
            'address.required' => 'Alamat wajib diisi.',
            'venue.required' => 'Lokasi venue wajib diisi.',
            'total_penawaran.required' => 'Total penawaran wajib diisi.',
        ]);
    }

    public function projectShow(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $detail = $this->finance->projectDetail($user, $id);

        if (! $detail) {
            return response()->json(['message' => 'Proyek tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }

    public function projectContract(Request $request, int $id): \Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $request->user();
        $file = $this->finance->projectContractFile($user, $id);

        if (! $file) {
            return response()->json(['message' => 'Kontrak tidak ditemukan.'], 404);
        }

        return response()->file($file['absolute'], [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$file['name'].'"',
        ]);
    }

    public function projectInvoice(Request $request, int $id): \Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $request->user();

        $order = $this->finance->scopedOrdersQuery($user)->find($id);

        if (! $order) {
            return response()->json(['message' => 'Proyek tidak ditemukan.'], 404);
        }

        return app(\App\Http\Controllers\InvoiceOrderController::class)->pdfResponse($order, false);
    }

    public function paymentProof(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $file = $this->finance->paymentProofFile($id);

        if (! $file) {
            return response()->json(['message' => 'Payment proof tidak ditemukan.'], 404);
        }

        return response()->file($file['absolute'], [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'inline; filename="'.$file['name'].'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $file['mtime'] ?? time()).' GMT',
        ]);
    }

    public function productShow(int $id): JsonResponse
    {
        $detail = $this->finance->productDetail($id);

        if (! $detail) {
            return response()->json(['message' => 'Paket tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }

    public function productPdf(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $product = \App\Models\Product::query()->find($id);

        if (! $product) {
            return response()->json(['message' => 'Paket tidak ditemukan.'], 404);
        }

        try {
            return app(\App\Http\Controllers\ProductDisplayController::class)->apiDownloadPdf($product);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'PDF paket gagal dibuat.'], 500);
        }
    }

    public function vendorShow(int $id): JsonResponse
    {
        $detail = $this->finance->vendorDetail($id);

        if (! $detail) {
            return response()->json(['message' => 'Vendor tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'in:wedding_payment,other_income,wedding_expense,operational_expense,other_expense'],
            'direction' => ['nullable', 'string', 'in:in,out'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);

        $result = $this->finance->transactions(
            $period['from'],
            $period['to'],
            $data['type'] ?? null,
            (int) ($data['limit'] ?? 100),
            $data['direction'] ?? null,
        );

        return response()->json($result);
    }

    public function reportSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'in:cash,profit_loss'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);

        return response()->json([
            'data' => $this->finance->reportSummary(
                $period['from'],
                $period['to'],
                $data['mode'] ?? 'cash',
            ),
        ]);
    }

    public function reportPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'in:cash,profit_loss'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);
        /** @var User $user */
        $user = $request->user();
        $payload = $this->finance->reportPdfPayload(
            $user,
            $period['from'],
            $period['to'],
            $data['mode'] ?? 'cash',
        );

        $pdf = Pdf::loadView($payload['view'], $payload['data']);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($payload['filename']);
    }

    public function reportExcel(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'in:cash,profit_loss'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);
        /** @var User $user */
        $user = $request->user();
        $payload = $this->finance->reportExcelPayload(
            $user,
            $period['from'],
            $period['to'],
            $data['mode'] ?? 'cash',
        );

        return Excel::download($payload['export'], $payload['filename']);
    }

    public function piutangs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:aktif,dibayar_sebagian,lunas,jatuh_tempo,dibatalkan'],
            'open_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json(
            $this->finance->piutangs(
                $data['status'] ?? null,
                (int) ($data['per_page'] ?? 20),
                (bool) ($data['open_only'] ?? false),
            )
        );
    }

    public function piutangShow(int $id): JsonResponse
    {
        $detail = $this->finance->piutangDetail($id);

        if (! $detail) {
            return response()->json(['message' => 'Piutang tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }
}
