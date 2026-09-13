<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SimulasiDisplayController;
use App\Models\SimulasiProduk;
use App\Services\MobileModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class MobileModuleController extends Controller
{
    public function __construct(
        private readonly MobileModuleService $modules,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->modules->catalog($request->user()),
        ]);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->modules->paginate(
            $request->user(),
            $key,
            $data['q'] ?? null,
            (int) ($data['per_page'] ?? 20),
        ));
    }

    public function form(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'data' => $this->modules->form($request->user(), $key, isset($data['id']) ? (int) $data['id'] : null),
        ]);
    }

    public function store(Request $request, string $key): JsonResponse
    {
        try {
            $record = $this->modules->store($request->user(), $key, $request->all());
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => 'Data berhasil disimpan.',
            'data' => $record,
        ], 201);
    }

    public function detail(Request $request, string $key, int $id): JsonResponse
    {
        $record = $this->modules->detail($request->user(), $key, $id);

        if (! $record) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $record]);
    }

    public function update(Request $request, string $key, int $id): JsonResponse
    {
        $record = $this->modules->update($request->user(), $key, $id, $request->all());

        if (! $record) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Data berhasil diperbarui.',
            'data' => $record,
        ]);
    }

    public function pdf(Request $request, string $key, int $id): Response
    {
        $simulasi = $this->simulasiRecord($request, $key, $id);

        if ($simulasi instanceof JsonResponse) {
            return $simulasi;
        }

        return app(SimulasiDisplayController::class)->downloadPdf($simulasi);
    }

    private function simulasiRecord(Request $request, string $key, int $id): SimulasiProduk|JsonResponse
    {
        if ($key !== 'simulasi') {
            return response()->json([
                'message' => 'Dokumen ini hanya tersedia untuk simulasi / draft kontrak.',
            ], 404);
        }

        $model = $this->modules->findModel($request->user(), $key, $id);

        if (! $model instanceof SimulasiProduk) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        return $model;
    }
}
