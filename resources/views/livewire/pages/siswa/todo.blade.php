<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Presensi;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads, WithPagination;

    // Presensi
    public $presensiHariIni = null;
    public $foto;
    public $mode = 'check_in';
    public $riwayat;

    // Tugas
    public $tugasList = [];
    protected $tugasPaginator;
    public $tugasDipilih = null;
    public $pengumpulan = null;
    public $file;
    public $catatan = '';

    public function mount()
    {
        $this->loadPresensi();
        $this->loadTugas();
    }

    public function loadPresensi()
    {
        $this->presensiHariIni = Presensi::where('user_id', auth()->id())
            ->whereDate('tanggal', today())
            ->first();

        if ($this->presensiHariIni) {
            $this->mode = $this->presensiHariIni->check_in && !$this->presensiHariIni->check_out ? 'check_out' : 'done';
        }

        $this->riwayat = Presensi::where('user_id', auth()->id())
            ->latest('tanggal')
            ->limit(5)
            ->get();
    }

    public function loadTugas()
    {
        $periodeId = auth()->user()->periode_id;
        $now = now();
        $this->tugasPaginator = Tugas::when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->where(function ($q) use ($now) {
                $q->whereNull('mulai')->orWhere('mulai', '<=', $now);
            })
            ->latest()
            ->paginate(10);
        $this->tugasList = $this->tugasPaginator->items();
    }

    // Presensi actions
    public function checkIn()
    {
        $periode = auth()->user()->periode;
        if ($periode && $periode->tanggal_mulai && $periode->tanggal_selesai && (today()->lt($periode->tanggal_mulai) || today()->gt($periode->tanggal_selesai))) {
            $tgl = $periode->tanggal_mulai->format('d/m/Y');
            $tgl2 = $periode->tanggal_selesai->format('d/m/Y');
            session()->flash('error', "Presensi hanya dapat dilakukan pada {$tgl} sampai {$tgl2}.");
            return;
        }

        $this->validate(['foto' => 'required|image|max:2048']);
        $path = $this->foto->store('presensi', 'public');

        Presensi::create([
            'user_id' => auth()->id(),
            'periode_id' => auth()->user()->periode_id,
            'tanggal' => today(),
            'check_in' => now(),
            'foto_check_in' => $path,
        ]);

        $this->foto = null;
        $this->loadPresensi();
    }

    public function checkOut()
    {
        $periode = auth()->user()->periode;
        if ($periode && $periode->tanggal_mulai && $periode->tanggal_selesai && (today()->lt($periode->tanggal_mulai) || today()->gt($periode->tanggal_selesai))) {
            $tgl = $periode->tanggal_mulai->format('d/m/Y');
            $tgl2 = $periode->tanggal_selesai->format('d/m/Y');
            session()->flash('error', "Presensi hanya dapat dilakukan pada {$tgl} sampai {$tgl2}.");
            return;
        }

        $this->validate(['foto' => 'required|image|max:2048']);
        $path = $this->foto->store('presensi', 'public');

        $this->presensiHariIni->update([
            'check_out' => now(),
            'foto_check_out' => $path,
        ]);

        $this->foto = null;
        $this->loadPresensi();
    }

    // Tugas actions
    public function pilihTugas($id)
    {
        $this->tugasDipilih = Tugas::findOrFail($id);
        $this->pengumpulan = PengumpulanTugas::where('tugas_id', $id)
            ->where('user_id', auth()->id())
            ->first();
        $this->file = null;
        $this->catatan = '';
    }

    public function batalPilih()
    {
        $this->tugasDipilih = null;
        $this->pengumpulan = null;
        $this->file = null;
        $this->catatan = '';
    }

    public function kumpul()
    {
        if ($this->pengumpulan) {
            return;
        }

        $this->validate([
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $path = $this->file ? $this->file->store('tugas', 'public') : null;

        PengumpulanTugas::create([
            'tugas_id' => $this->tugasDipilih->id,
            'user_id' => auth()->id(),
            'file' => $path,
            'catatan' => $this->catatan,
            'status' => 'terkirim',
            'submitted_at' => now(),
        ]);

        $this->pilihTugas($this->tugasDipilih->id);
    }
}; ?>

<div class="p-4 md:p-0">
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] -mx-4 md:-mx-0 mb-6">
        <h1 class="text-white text-lg font-extrabold uppercase">To Do</h1>
        <p class="text-white/70 text-xs font-bold">{{ now()->format('l, d F Y') }}</p>
    </div>

    {{-- Presensi Section --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-extrabold text-dark uppercase text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Presensi Hari Ini
            </h2>
            <div class="w-3 h-3 rounded-full {{ $presensiHariIni ? 'bg-accent' : 'bg-red-400' }} border-2 border-dark"></div>
        </div>

        @if (session('error'))
            <div class="bg-red-100 border-3 border-red-500 p-3 mb-3 text-xs font-bold text-red-700">{{ session('error') }}</div>
        @endif

        @if ($mode === 'done')
            <div class="text-center py-3">
                <div class="inline-block bg-accent border-3 border-dark px-4 py-2 mb-2">
                    <span class="font-extrabold text-lg text-dark">✓</span>
                </div>
                <p class="font-bold text-dark text-sm">Presensi selesai hari ini</p>
                <p class="text-xs font-semibold text-dark/60 mt-1">
                    Check In: {{ $presensiHariIni->check_in->format('H:i') }}
                    @if ($presensiHariIni->check_out)
                        • Check Out: {{ $presensiHariIni->check_out->format('H:i') }}
                    @endif
                </p>
            </div>
        @else
            <div class="text-center py-3">
                <div class="inline-block bg-highlight border-3 border-dark px-4 py-2 mb-2">
                    <span class="font-extrabold text-lg text-dark">{{ $mode === 'check_in' ? '↗' : '↙' }}</span>
                </div>
                <h3 class="font-extrabold text-dark uppercase text-xs mb-3">
                    {{ $mode === 'check_in' ? 'Check In Masuk' : 'Check Out Pulang' }}
                </h3>

                <div class="mb-3 max-w-xs mx-auto">
                    <input type="file" wire:model="foto" accept="image/*" capture="environment"
                           class="block w-full text-sm font-semibold file:mr-4 file:py-2 file:px-4 file:border-3 file:border-dark file:bg-highlight file:text-dark file:font-bold file:text-xs file:uppercase file:cursor-pointer">
                    @error('foto') <span class="text-xs font-bold text-red-500 block mt-1">{{ $message }}</span> @enderror
                    <p wire:loading wire:target="foto" class="text-[10px] font-bold text-secondary mt-1 animate-pulse">Mengupload foto...</p>
                </div>

                @if ($foto)
                    <div class="mb-3">
                        <img src="{{ $foto->temporaryUrl() }}" class="w-24 h-24 object-cover border-3 border-dark mx-auto shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-[10px] font-bold text-accent mt-1">Upload selesai ✓</p>
                    </div>
                @endif

                <button wire:click="{{ $mode === 'check_in' ? 'checkIn' : 'checkOut' }}"
                        wire:loading.attr="disabled"
                        wire:target="checkIn,checkOut,foto"
                        class="bg-primary text-white border-3 border-dark px-6 py-2 font-bold text-xs uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all disabled:opacity-50">
                    {{ $mode === 'check_in' ? 'Check In' : 'Check Out' }}
                </button>
            </div>
        @endif

        {{-- Riwayat Singkat --}}
        @if ($riwayat->isNotEmpty())
            <div class="border-t-3 border-dark/10 pt-3 mt-3">
                <div class="flex gap-2 flex-wrap">
                    @foreach ($riwayat as $p)
                        <div class="text-center border-2 border-dark/20 px-2 py-1 {{ $p->tanggal->isToday() ? 'bg-accent/20' : '' }}">
                            <span class="text-[9px] font-bold block">{{ $p->tanggal->format('d/m') }}</span>
                            <span class="text-[9px] font-semibold {{ $p->check_in ? 'text-accent' : 'text-red-400' }}">
                                {{ $p->check_in ? $p->check_in->format('H:i') : '-' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Tugas Section --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-highlight" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Tugas
        </h2>

        @if ($tugasDipilih)
            {{-- Detail Tugas --}}
            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-extrabold text-dark uppercase text-xs">{{ $tugasDipilih->judul }}</h3>
                    <button wire:click="batalPilih" class="text-[10px] font-bold text-red-500 underline underline-offset-2">Kembali</button>
                </div>
                <p class="text-xs font-semibold text-dark/70 mb-3">{{ $tugasDipilih->deskripsi }}</p>
                <div class="bg-highlight border-3 border-dark px-3 py-1.5 mb-3 inline-block">
                    <span class="text-[10px] font-bold text-dark">Deadline: {{ $tugasDipilih->deadline->format('d M Y H:i') }}</span>
                </div>

                @if ($pengumpulan)
                    <div class="bg-highlight/30 border-3 border-dark p-3 mb-3">
                        <p class="text-xs font-bold uppercase mb-1">
                            Status:
                            <span class="{{ $pengumpulan->status === 'dinilai' ? 'text-accent' : 'text-secondary' }}">
                                {{ $pengumpulan->status === 'dinilai' ? 'Sudah Dinilai' : 'Terkirim' }}
                            </span>
                        </p>
                        @if ($pengumpulan->submitted_at)
                            <p class="text-[10px] font-semibold text-dark/60">Dikirim: {{ $pengumpulan->submitted_at->format('d M Y H:i') }}</p>
                        @endif
                        @if ($pengumpulan->nilai)
                            <p class="text-xs font-extrabold text-dark mt-1">Nilai: {{ $pengumpulan->nilai }}</p>
                        @endif
                    </div>
                @else
                    <form wire:submit="kumpul" class="space-y-3 mt-3 border-t-3 border-dark/10 pt-3">
                        <div>
                            <x-input-label value="Upload File (opsional)" />
                            <input type="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm font-semibold file:mr-4 file:py-2 file:px-4 file:border-3 file:border-dark file:bg-secondary file:text-white file:font-bold file:text-xs file:uppercase file:cursor-pointer mt-1">
                            @error('file') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <x-input-label value="Catatan (opsional)" />
                            <textarea wire:model="catatan" rows="2" class="w-full border-3 border-dark p-2 text-sm font-semibold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary" placeholder="Tulis catatan..."></textarea>
                            @error('catatan') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <x-primary-button type="submit">Kirim Tugas</x-primary-button>
                    </form>
                @endif
            </div>
        @else
            {{-- Daftar Tugas --}}
            <div class="space-y-2">
                @if ($this->tugasPaginator)
                    {{ $this->tugasPaginator->links() }}
                @endif
                @forelse ($this->tugasList as $tugas)
                    @php $sudah = $tugas->pengumpulan()->where('user_id', auth()->id())->first(); @endphp
                    <button wire:click="pilihTugas({{ $tugas->id }})" class="w-full text-left border-3 border-dark p-3 hover:bg-gray-50 transition-colors shadow-[3px_3px_0px_0px_#1a1a1a] hover:shadow-[1px_1px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 {{ $sudah ? 'bg-accent' : 'bg-secondary' }} border-2 border-dark flex items-center justify-center text-white font-extrabold text-xs shrink-0">
                                @if ($sudah)
                                    <svg class="w-4 h-4 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <span>{{ $loop->iteration }}</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-xs uppercase truncate">{{ $tugas->judul }}</h3>
                                <p class="text-[10px] font-semibold text-dark/50">Deadline: {{ $tugas->deadline->format('d M Y H:i') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-dark/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-6">
                        <p class="font-bold text-dark text-sm">Belum ada tugas</p>
                        <p class="text-xs font-semibold text-dark/50 mt-1">Tugas akan muncul setelah admin menambahkannya</p>
                    </div>
                @endforelse
            </div>
            @if ($this->tugasPaginator)
                {{ $this->tugasPaginator->links() }}
            @endif
        @endif
    </div>
</div>
