<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public $tugasList;
    public $tugasDipilih = null;
    public $pengumpulan = null;
    public $file;
    public $catatan = '';

    public function mount()
    {
        $this->loadTugas();
    }

    public function loadTugas()
    {
        $periodeId = auth()->user()->periode_id;
        $this->tugasList = Tugas::when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->latest()
            ->get();
    }

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

    public function getSudahDikumpulkanProperty($tugasId)
    {
        return PengumpulanTugas::where('tugas_id', $tugasId)
            ->where('user_id', auth()->id())
            ->exists();
    }
}; ?>

<div class="p-4 md:p-0">
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] -mx-4 md:-mx-0 mb-6">
        <h1 class="text-white text-lg font-extrabold uppercase">Tugas</h1>
        <p class="text-white/70 text-xs font-bold">Daftar tugas masa orientasi</p>
    </div>

    @if ($tugasDipilih)
        {{-- Detail Tugas --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-extrabold text-dark uppercase text-sm">{{ $tugasDipilih->judul }}</h2>
                <button wire:click="batalPilih" class="text-xs font-bold text-red-500 underline underline-offset-2">Kembali</button>
            </div>
            <p class="text-sm font-semibold text-dark/70 mb-4">{{ $tugasDipilih->deskripsi }}</p>
            <div class="bg-highlight border-3 border-dark px-3 py-2 mb-4 inline-block">
                <span class="text-xs font-bold text-dark">Deadline: {{ $tugasDipilih->deadline->format('d M Y H:i') }}</span>
            </div>

            @if ($pengumpulan && $pengumpulan->status === 'dinilai')
                <div class="bg-accent border-3 border-dark p-3 mb-4">
                    <span class="font-extrabold text-dark text-lg">Nilai: {{ $pengumpulan->nilai }}</span>
                </div>
            @endif

            @if ($pengumpulan)
                <div class="bg-highlight/30 border-3 border-dark p-3 mb-4">
                    <p class="text-xs font-bold uppercase mb-1">Status: 
                        <span class="{{ $pengumpulan->status === 'dinilai' ? 'text-accent' : 'text-secondary' }}">
                            {{ $pengumpulan->status === 'dinilai' ? 'Sudah Dinilai' : 'Terkirim' }}
                        </span>
                    </p>
                    @if ($pengumpulan->submitted_at)
                        <p class="text-[10px] font-semibold text-dark/60">Dikirim: {{ $pengumpulan->submitted_at->format('d M Y H:i') }}</p>
                    @endif
                </div>
            @endif

            @if (!$pengumpulan)
                <form wire:submit="kumpul" class="space-y-3 mt-4 border-t-3 border-dark/10 pt-4">
                    <div>
                        <x-input-label value="Upload File (opsional)" />
                        <input type="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm font-semibold file:mr-4 file:py-2 file:px-4 file:border-3 file:border-dark file:bg-secondary file:text-white file:font-bold file:text-xs file:uppercase file:cursor-pointer mt-1">
                        @error('file') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-input-label value="Catatan (opsional)" />
                        <textarea wire:model="catatan" rows="3" class="w-full border-3 border-dark p-2 text-sm font-semibold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary" placeholder="Tulis catatan..."></textarea>
                        @error('catatan') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <x-primary-button type="submit">Kirim Tugas</x-primary-button>
                </form>
            @endif
        </div>
    @else
        {{-- Daftar Tugas --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
            <div class="space-y-3">
                @forelse ($tugasList as $tugas)
                    <button wire:click="pilihTugas({{ $tugas->id }})" class="w-full text-left border-3 border-dark p-4 hover:bg-gray-50 transition-colors shadow-[3px_3px_0px_0px_#1a1a1a] hover:shadow-[1px_1px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-extrabold text-dark text-sm uppercase">{{ $tugas->judul }}</h3>
                                <p class="text-xs font-semibold text-dark/60 mt-1 line-clamp-2">{{ $tugas->deskripsi }}</p>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="text-[10px] font-bold text-dark/50">Deadline: {{ $tugas->deadline->format('d M Y') }}</span>
                                    @php
                                        $sudah = \App\Models\PengumpulanTugas::where('tugas_id', $tugas->id)->where('user_id', auth()->id())->first();
                                    @endphp
                                    @if ($sudah)
                                        <span class="text-[10px] font-bold {{ $sudah->status === 'dinilai' ? 'text-accent' : 'text-secondary' }}">{{ $sudah->status === 'dinilai' ? '✓ Dinilai' : '✓ Terkirim' }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="bg-secondary/20 border-2 border-dark p-2 shrink-0">
                                <svg class="w-4 h-4 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-8">
                        <div class="inline-block bg-highlight border-3 border-dark px-4 py-2 mb-3">
                            <span class="font-extrabold text-lg text-dark">📋</span>
                        </div>
                        <p class="font-bold text-dark text-sm">Belum ada tugas</p>
                        <p class="text-xs font-semibold text-dark/50 mt-1">Tugas akan muncul setelah admin menambahkannya</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
