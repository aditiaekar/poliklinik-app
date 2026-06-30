<x-layouts.app title="Data Obat">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">
            Data Obat
        </h2>

        <a href="{{ route('obat.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5
                  bg-primary hover:bg-primary/90
                  text-white text-sm font-semibold
                  rounded-xl transition">
            <i class="fas fa-plus text-xs"></i>
            Tambah Obat
        </a>
    </div>

    {{-- Card --}}
    <div class="card bg-base-100 shadow-md rounded-2 border">
        <div class="card-body p-0">

            <div class="overflow-x-auto">
                <table class="table w-full">

                    {{-- Table Head --}}
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Nama Obat</th>
                            <th class="px-6 py-4">Kemasan</th>
                            <th class="px-6 py-4">Harga</th>
                            <th class="px-6 py-4">Stok</th>
                            <th class="px-6 py-4">Atur Stok</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    {{-- Table Body --}}
                    <tbody class="text-sm text-slate-700">
                        @forelse($obats as $obat)
                        <tr class="border-t border-slate-100 hover:bg-slate-50 transition">

                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $obat->nama_obat }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-block px-3 py-1 text-xs font-semibold
                                             rounded-full bg-green-100 text-green-600">
                                    {{ $obat->kemasan ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 font-semibold text-slate-800">
                                Rp {{ number_format($obat->harga, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-slate-800">
                                        {{ $obat->stok }}
                                    </span>

                                    @if($obat->status_stok === 'habis')
                                        <span class="inline-block w-fit px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-600">
                                            Stok Habis
                                        </span>
                                    @elseif($obat->status_stok === 'menipis')
                                        <span class="inline-block w-fit px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                                            Stok Menipis
                                        </span>
                                    @else
                                        <span class="inline-block w-fit px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-600">
                                            Stok Aman
                                        </span>
                                    @endif

                                    <span class="text-xs text-slate-400">
                                        Minimum: {{ $obat->stok_minimum }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2 min-w-52">
                                    <form action="{{ route('obat.tambah-stok', $obat->id) }}" method="POST" class="flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="jumlah" min="1" placeholder="Jumlah" required
                                            class="input input-bordered input-sm w-24">
                                        <button type="submit" class="btn btn-sm bg-emerald-500 hover:bg-emerald-600 text-white border-none">
                                            Tambah
                                        </button>
                                    </form>

                                    <form action="{{ route('obat.kurangi-stok', $obat->id) }}" method="POST" class="flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="jumlah" min="1" placeholder="Jumlah" required
                                            class="input input-bordered input-sm w-24">
                                        <button type="submit" class="btn btn-sm bg-orange-500 hover:bg-orange-600 text-white border-none">
                                            Kurangi
                                        </button>
                                    </form>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">

                                    {{-- Edit --}}
                                    <a href="{{ route('obat.edit', $obat->id) }}" class="inline-flex items-center gap-1 px-4 py-2
                                              bg-amber-500 hover:bg-amber-600
                                              text-white text-xs font-semibold
                                              rounded-lg transition">
                                        <i class="fas fa-pen text-xs"></i>
                                        Edit
                                    </a>

                                    {{-- Delete --}}
                                    <form action="{{ route('obat.destroy', $obat->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            onclick="return confirm('Yakin ingin menghapus obat ini?')" class="inline-flex items-center gap-1 px-4 py-2
                                                   bg-red-500 hover:bg-red-600
                                                   text-white text-xs font-semibold
                                                   rounded-lg transition">
                                            <i class="fas fa-trash text-xs"></i>
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-3 block"></i>
                                Belum ada data obat
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</x-layouts.app>