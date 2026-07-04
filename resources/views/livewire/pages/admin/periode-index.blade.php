<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Periode;
use App\Models\User;

new #[Layout('layouts.app')] class extends Component
{
    public $periodeList;
    public $showForm = false;
    public $editId = null;

    // Period form
    public $nama = '';
    public $tanggal_mulai = '';
    public $tanggal_selesai = '';

    // Participant management
    public $kelolaPeriode = null;
    public $siswaList = [];
    public $siswaTersedia = [];
    public $cariSiswa = '';

    public function mount()
    {
        $this->loadPeriodes();
    }

    public function loadPeriodes()
    {
        $this->periodeList = Periode::withCount('siswa', 'presensis', 'tugas')
            ->latest()
            ->get();
    }

    public function resetForm()
    {
        $this->reset(['nama', 'tanggal_mulai', 'tanggal_selesai', 'editId', 'showForm']);
    }

    public function buatBaru()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $p = Periode::findOrFail($id);
        $this->editId = $p->id;
        $this->nama = $p->nama;
        $this->tanggal_mulai = $p->tanggal_mulai->format('Y-m-d');
        $this->tanggal_selesai = $p->tanggal_selesai->format('Y-m-d');
        $this->showForm = true;
    }

    public function simpan()
    {
        $this->validate([
            'nama' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $data = [
            'nama' => $this->nama,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
        ];

        if ($this->editId) {
            Periode::findOrFail($this->editId)->update($data);
        } else {
            Periode::create($data);
        }

        $this->resetForm();
        $this->loadPeriodes();
    }

    public function hapus($id)
    {
        $p = Periode::findOrFail($id);
        if ($p->presensis()->exists() || $p->tugas()->exists()) {
            session()->flash('error', 'Tidak dapat menghapus periode yang sudah memiliki data presensi atau tugas.');
            return;
        }
        $p->users()->update(['periode_id' => null]);
        $p->delete();
        $this->loadPeriodes();
    }

    public function setActive($id)
    {
        Periode::where('is_active', true)->update(['is_active' => false]);
        Periode::findOrFail($id)->update(['is_active' => true]);
        $this->loadPeriodes();
    }

    public function kelolaSiswa($id)
    {
        $this->kelolaPeriode = Periode::findOrFail($id);
        $this->cariSiswa = '';
        $this->loadSiswa();
    }

    public function loadSiswa()
    {
        if (!$this->kelolaPeriode) return;

        $this->siswaList = User::where('role', 'siswa')
            ->where('periode_id', $this->kelolaPeriode->id)
            ->when($this->cariSiswa, fn($q) => $q->where(function($q) {
                $q->where('name', 'like', "%{$this->cariSiswa}%")
                  ->orWhere('nis', 'like', "%{$this->cariSiswa}%")
                  ->orWhere('kelas', 'like', "%{$this->cariSiswa}%");
            }))
            ->orderBy('name')
            ->get();

        $this->siswaTersedia = User::where('role', 'siswa')
            ->whereNull('periode_id')
            ->when($this->cariSiswa, fn($q) => $q->where(function($q) {
                $q->where('name', 'like', "%{$this->cariSiswa}%")
                  ->orWhere('nis', 'like', "%{$this->cariSiswa}%")
                  ->orWhere('kelas', 'like', "%{$this->cariSiswa}%");
            }))
            ->orderBy('name')
            ->get();
    }

    public function updatedCariSiswa()
    {
        $this->loadSiswa();
    }

    public function tambahSiswa($userId)
    {
        User::findOrFail($userId)->update(['periode_id' => $this->kelolaPeriode->id]);
        $this->loadSiswa();
    }

    public function hapusSiswa($userId)
    {
        User::findOrFail($userId)->update(['periode_id' => null]);
        $this->loadSiswa();
    }

    public function tutupKelola()
    {
        $this->kelolaPeriode = null;
        $this->siswaList = [];
        $this->siswaTersedia = [];
        $this->cariSiswa = '';
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Kelola Periode</h1>
                <p class="text-white/70 text-xs font-bold">Atur periode event Fortasi tahunan</p>
            </div>
            <button wire:click="buatBaru" class="bg-accent text-dark border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
                + Periode
            </button>
        </div>
    </div>

    {{-- Error Flash --}}
    @if (session('error'))
        <div class="bg-red-500 border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4 mb-6">
            <p class="font-bold text-white text-sm">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Form --}}
    @if ($showForm)
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4">{{ $editId ? 'Edit Periode' : 'Buat Periode Baru' }}</h2>
            <form wire:submit="simpan" class="space-y-3">
                <div>
                    <x-input-label value="Nama Periode" />
                    <x-text-input wire:model="nama" class="w-full" placeholder="Contoh: Fortasi 2026" />
                    @error('nama') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label value="Tanggal Mulai" />
                        <input type="date" wire:model="tanggal_mulai" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                        @error('tanggal_mulai') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-input-label value="Tanggal Selesai" />
                        <input type="date" wire:model="tanggal_selesai" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                        @error('tanggal_selesai') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <x-primary-button type="submit">Simpan</x-primary-button>
                    <x-secondary-button type="button" wire:click="resetForm">Batal</x-secondary-button>
                </div>
            </form>
        </div>
    @endif

    {{-- Kelola Siswa --}}
    @if ($kelolaPeriode)
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] mb-6 overflow-hidden">
            <div class="bg-dark text-white px-5 py-3 flex items-center justify-between">
                <h2 class="font-extrabold text-sm uppercase">Kelola Peserta: {{ $kelolaPeriode->nama }}</h2>
                <button wire:click="tutupKelola" class="text-[10px] font-bold text-white/70 underline underline-offset-2">Tutup</button>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-dark border-3 border-dark p-2 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="cariSiswa" placeholder="Cari nama atau NIS..." class="flex-1 border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Peserta Saat Ini --}}
                    <div>
                        <h3 class="font-extrabold text-dark uppercase text-xs mb-3">Peserta ({{ $siswaList->count() }})</h3>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            @forelse ($siswaList as $s)
                                <div class="flex items-center justify-between border-3 border-dark/10 p-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-8 h-8 bg-secondary border-2 border-dark flex items-center justify-center text-white font-extrabold text-xs shrink-0">
                                            {{ substr($s->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs truncate">{{ $s->name }}</p>
                                            <p class="text-[9px] font-semibold text-dark/50">{{ $s->kelas }} &middot; {{ $s->nis }}</p>
                                        </div>
                                    </div>
                                    <button wire:click="hapusSiswa({{ $s->id }})" class="bg-red-500 text-white border-2 border-dark p-1 flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Keluarkan">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @empty
                                <p class="text-xs font-semibold text-dark/50 text-center py-4">Belum ada peserta</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Tersedia --}}
                    <div>
                        <h3 class="font-extrabold text-dark uppercase text-xs mb-3">Tersedia ({{ $siswaTersedia->count() }})</h3>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            @forelse ($siswaTersedia as $s)
                                <div class="flex items-center justify-between border-3 border-dark/10 p-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-8 h-8 bg-accent border-2 border-dark flex items-center justify-center text-dark font-extrabold text-xs shrink-0">
                                            {{ substr($s->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs truncate">{{ $s->name }}</p>
                                            <p class="text-[9px] font-semibold text-dark/50">{{ $s->kelas }} &middot; {{ $s->nis }}</p>
                                        </div>
                                    </div>
                                    <button wire:click="tambahSiswa({{ $s->id }})" class="bg-accent text-dark border-2 border-dark p-1 flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Tambah">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                            @empty
                                <p class="text-xs font-semibold text-dark/50 text-center py-4">Tidak ada siswa tersedia</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Daftar Periode --}}
    <div class="space-y-4">
        @forelse ($periodeList as $p)
            <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5 {{ $p->is_active ? 'border-accent' : '' }}">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-extrabold text-dark text-sm uppercase">{{ $p->nama }}</h3>
                            @if ($p->is_active)
                                <span class="text-[9px] font-bold bg-accent border-2 border-dark px-1.5 py-0.5 uppercase">Aktif</span>
                            @endif
                        </div>
                        <p class="text-xs font-semibold text-dark/60 mt-1">
                            {{ $p->tanggal_mulai->format('d M Y') }} — {{ $p->tanggal_selesai->format('d M Y') }}
                        </p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        @if (!$p->is_active)
                            <button wire:click="setActive({{ $p->id }})" class="bg-accent text-dark border-2 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Aktifkan">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                        @endif
                        <button wire:click="kelolaSiswa({{ $p->id }})" class="bg-secondary text-white border-2 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Kelola Peserta">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                        </button>
                        <button wire:click="edit({{ $p->id }})" class="bg-highlight text-dark border-2 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus periode ini? Data presensi & tugas di periode ini akan tetap tersimpan." class="bg-red-500 text-white border-2 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Hapus">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 border-t-3 border-dark/10 pt-3">
                    <div class="text-center">
                        <span class="block font-extrabold text-dark">{{ $p->siswa_count }}</span>
                        <span class="text-[9px] font-bold uppercase text-dark/50">Peserta</span>
                    </div>
                    <div class="text-center">
                        <span class="block font-extrabold text-dark">{{ $p->presensis_count }}</span>
                        <span class="text-[9px] font-bold uppercase text-dark/50">Presensi</span>
                    </div>
                    <div class="text-center">
                        <span class="block font-extrabold text-dark">{{ $p->tugas_count }}</span>
                        <span class="text-[9px] font-bold uppercase text-dark/50">Tugas</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-8 text-center">
                <p class="font-bold text-dark/50">Belum ada periode. Klik "+ Periode" untuk membuat.</p>
            </div>
        @endforelse
    </div>
</div>
