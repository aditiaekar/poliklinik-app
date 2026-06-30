<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObatController extends Controller
{
    public function index()
    {
        // Ambil semua data obat dan urutkan berdasarkan nama obat
        $obats = Obat::orderBy('nama_obat')->get();

        return view('admin.obat.index', compact('obats'));
    }

    public function create()
    {
        return view('admin.obat.create');
    }

    // Menyimpan data obat baru.
    public function store(Request $request)
    {
        // Validasi input dari form tambah obat
        $validated = $request->validate($this->rules());

        // Simpan data obat ke database
        Obat::create($validated);

        return redirect()->route('obat.index')
            ->with('success', 'Data obat berhasil dibuat.');
    }

    // Menampilkan form edit obat.
    public function edit(string $id)
    {
        // Cari data obat berdasarkan ID
        $obat = Obat::findOrFail($id);

        return view('admin.obat.edit')->with([
            'obat' => $obat,
        ]);
    }

    // Memperbarui data obat.
    public function update(Request $request, string $id)
    {
        // Validasi input dari form edit obat
        $validated = $request->validate($this->rules());

        // Cari data obat berdasarkan ID
        $obat = Obat::findOrFail($id);

        // Update data obat
        $obat->update($validated);

        return redirect()->route('obat.index')
            ->with('success', 'Data obat berhasil diperbarui.');
    }

    // Menambah stok obat secara manual.
    public function tambahStok(Request $request, Obat $obat)
    {
        // Validasi jumlah stok yang akan ditambahkan
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($obat, $validated) {
            /**
             * lockForUpdate digunakan untuk mengunci baris obat saat stok diproses.
             * Tujuannya agar stok lebih aman jika ada proses bersamaan.
             */
            $obat = Obat::whereKey($obat->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Tambahkan stok sesuai jumlah yang diinput admin
            $obat->increment('stok', $validated['jumlah']);
        });

        return redirect()->route('obat.index')
            ->with('success', 'Stok obat berhasil ditambahkan.');
    }

    // Mengurangi stok obat secara manual.
    public function kurangiStok(Request $request, Obat $obat)
    {
        // Validasi jumlah stok yang akan dikurangi
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $result = DB::transaction(function () use ($obat, $validated) {
            /**
             * Ambil ulang data obat dengan lockForUpdate
             * agar stok tidak berubah di tengah proses transaksi.
             */
            $obat = Obat::whereKey($obat->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Jika jumlah pengurangan melebihi stok, proses dibatalkan
            if ($validated['jumlah'] > $obat->stok) {
                return false;
            }

            // Kurangi stok sesuai jumlah yang diinput admin
            $obat->decrement('stok', $validated['jumlah']);

            return true;
        });

        // Jika stok tidak cukup, tampilkan pesan error
        if (! $result) {
            return redirect()->route('obat.index')
                ->with('error', 'Stok obat tidak mencukupi untuk dikurangi.');
        }

        return redirect()->route('obat.index')
            ->with('success', 'Stok obat berhasil dikurangi.');
    }

    // Menghapus data obat.
    public function destroy(string $id)
    {
        // Cari obat berdasarkan ID
        $obat = Obat::findOrFail($id);

        // Hapus data obat
        $obat->delete();

        return redirect()->route('obat.index')
            ->with('success', 'Data obat berhasil dihapus.');
    }

    /**
     * Aturan validasi untuk tambah dan edit obat.
     */
    private function rules(): array
    {
        return [
            // Nama obat wajib diisi
            'nama_obat' => ['required', 'string', 'max:255'],

            // Kemasan boleh kosong
            'kemasan' => ['nullable', 'string', 'max:35'],

            // Harga wajib angka dan tidak boleh minus
            'harga' => ['required', 'integer', 'min:0'],

            // Stok wajib angka dan tidak boleh minus
            'stok' => ['required', 'integer', 'min:0'],

            // Stok minimum wajib angka dan tidak boleh minus
            'stok_minimum' => ['required', 'integer', 'min:0'],
        ];
    }
}