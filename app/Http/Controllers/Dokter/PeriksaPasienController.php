<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\DetailPeriksa;
use App\Models\Obat;
use App\Models\Periksa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriksaPasienController extends Controller
{
    // Menampilkan daftar pasien yang perlu diperiksa oleh dokter login.
    public function index()
    {
        $dokterId = Auth::id();

        // Ambil daftar pasien berdasarkan jadwal dokter login.
        $daftarPasien = DaftarPoli::with(['pasien', 'jadwalPeriksa', 'periksas'])
            ->whereHas('jadwalPeriksa', function ($query) use ($dokterId) {
                $query->where('id_dokter', $dokterId);
            })
            ->orderBy('no_antrian')
            ->get();

        return view('dokter.periksa-pasien.index', compact('daftarPasien'));
    }

    // Menampilkan form pemeriksaan pasien.
    public function create($id)
    {
        // Ambil semua data obat untuk dipilih dokter
        $obats = Obat::orderBy('nama_obat')->get();

        return view('dokter.periksa-pasien.create', compact('obats', 'id'));
    }

    // Menyimpan data pemeriksaan pasien.
    public function store(Request $request)
    {
        // Validasi input utama pemeriksaan
        $request->validate([
            'id_daftar_poli' => ['required', 'exists:daftar_poli,id'],
            'obat_json' => ['required', 'json'],
            'catatan' => ['nullable', 'string'],
        ]);

        // Decode obat_json dari form.
        $obatIds = collect(json_decode($request->obat_json, true))
            ->filter()   // hapus nilai kosong/null
            ->unique()   // cegah obat yang sama masuk dua kali
            ->values();  // reset index array

        // Jika dokter belum memilih obat, tampilkan error
        if ($obatIds->isEmpty()) {
            throw ValidationException::withMessages([
                'obat_json' => 'Pilih minimal satu obat.',
            ]);
        }

        /**
         * Gunakan transaksi database agar proses pemeriksaan dan stok aman.
         *
         * Jika salah satu proses gagal, semua proses akan dibatalkan.
         */
        DB::transaction(function () use ($request, $obatIds) {
            /**
             * Ambil data obat berdasarkan ID yang dipilih dokter.
             * lockForUpdate digunakan supaya stok tidak berubah saat transaksi berjalan.
             */
            $obats = Obat::whereIn('id', $obatIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Pastikan semua ID obat yang dikirim benar-benar ada di database
            if ($obats->count() !== $obatIds->count()) {
                throw ValidationException::withMessages([
                    'obat_json' => 'Data obat tidak valid.',
                ]);
            }

            /**
             * Cek apakah ada obat yang stoknya habis.
             *
             * Karena detail_periksa belum memiliki jumlah obat,
             * maka setiap obat yang dipilih dianggap menggunakan stok 1.
             */
            $stokTidakCukup = $obats->filter(function ($obat) {
                return $obat->stok < 1;
            });

            // Jika ada stok obat tidak cukup, proses pemeriksaan dibatalkan
            if ($stokTidakCukup->isNotEmpty()) {
                $namaObat = $stokTidakCukup->pluck('nama_obat')->implode(', ');

                throw ValidationException::withMessages([
                    'obat_json' => 'Stok obat tidak mencukupi: ' . $namaObat,
                ]);
            }

            /**
             * Hitung total harga obat yang dipilih.
             *
             * Total biaya pemeriksaan = total harga obat + biaya dokter 150000.
             */
            $totalHargaObat = $obatIds->sum(function ($idObat) use ($obats) {
                return $obats[$idObat]->harga;
            });

            // Simpan data pemeriksaan utama
            $periksa = Periksa::create([
                'id_daftar_poli' => $request->id_daftar_poli,
                'tgl_periksa' => now(),
                'catatan' => $request->catatan,
                'biaya_periksa' => $totalHargaObat + 150000,
            ]);

            /**
             * Simpan detail obat dan kurangi stok.
             *
             * Setiap obat yang dipilih:
             * - masuk ke tabel detail_periksa
             * - stok obat dikurangi 1
             */
            foreach ($obatIds as $idObat) {
                DetailPeriksa::create([
                    'id_periksa' => $periksa->id,
                    'id_obat' => $idObat,
                ]);

                // Kurangi stok obat otomatis karena obat digunakan dalam resep
                $obats[$idObat]->decrement('stok');
            }
        });

        return redirect()->route('periksa-pasien.index')
            ->with('success', 'Data periksa berhasil disimpan.');
    }
}