<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Models\PaketLayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LayananApiController extends Controller
{
    /**
     * =========================
     * GET LIST LAYANAN & PAKET
     * =========================
     */
    public function index(Request $request)
    {
        try {
            $layananQuery = Layanan::with('kategori');
            $paketQuery   = PaketLayanan::with(['kategori', 'layanans']);

            if ($request->filled('kategori')) {
                $layananQuery->where('kategori_id', $request->kategori);
                $paketQuery->where('kategori_id', $request->kategori);
            }

            if ($request->filled('search')) {
                $search = $request->search;

                $layananQuery->where(function ($q) use ($search) {
                    $q->where('nama_layanan', 'like', "%{$search}%")
                      ->orWhere('deskripsi', 'like', "%{$search}%");
                });

                $paketQuery->where(function ($q) use ($search) {
                    $q->where('nama_paket', 'like', "%{$search}%")
                      ->orWhere('deskripsi', 'like', "%{$search}%");
                });
            }

            $layanans = $layananQuery->get()->map(function ($item) {
                $item->type = 'layanan';
                return $item;
            });

            $pakets = $paketQuery->get()->map(function ($item) {
                $item->type = 'paket';
                return $item;
            });

            $results = $layanans->concat($pakets)->sortByDesc('created_at');

            return response()->json([
                'status' => 'success',
                'data' => $results->values()
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * SHOW DETAIL LAYANAN
     * =========================
     */
    public function show($id)
    {
        try {
            $layanan = Layanan::with('kategori')->find($id);

            if (!$layanan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Layanan tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $layanan
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail layanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * STORE LAYANAN / PAKET
     * =========================
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:layanan,paket',
                'kategori_id' => 'required|exists:kategoris,id',
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'harga' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->type === 'layanan') {
                $data = Layanan::create([
                    'kategori_id' => $request->kategori_id,
                    'nama_layanan' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga_layanan' => $request->harga,
                ]);
            } else {
                $data = PaketLayanan::create([
                    'kategori_id' => $request->kategori_id,
                    'nama_paket' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga_paket' => $request->harga,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'data' => $data
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * UPDATE LAYANAN / PAKET
     * =========================
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:layanan,paket',
                'kategori_id' => 'required|exists:kategoris,id',
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'harga' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->type === 'layanan') {
                $data = Layanan::find($id);
                if (!$data) {
                    return response()->json(['message' => 'Layanan tidak ditemukan'], 404);
                }

                $data->update([
                    'kategori_id' => $request->kategori_id,
                    'nama_layanan' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga_layanan' => $request->harga,
                ]);
            } else {
                $data = PaketLayanan::find($id);
                if (!$data) {
                    return response()->json(['message' => 'Paket tidak ditemukan'], 404);
                }

                $data->update([
                    'kategori_id' => $request->kategori_id,
                    'nama_paket' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga_paket' => $request->harga,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diperbarui',
                'data' => $data
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * DELETE LAYANAN / PAKET
     * =========================
     */
    public function destroy(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:layanan,paket',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->type === 'layanan'
                ? Layanan::find($id)
                : PaketLayanan::find($id);

            if (!$data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dihapus'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
