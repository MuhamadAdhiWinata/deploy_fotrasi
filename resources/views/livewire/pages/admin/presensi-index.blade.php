<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Presensi;
use App\Models\User;

new #[Layout('layouts.app')] class extends Component
{
    public $presensiList;
    public $tanggal;
    public $selectedTanggal;
    public $detailSiswa = null;
    public $cari = '';
    public $filterKelas = '';

    public function mount()
    {
        $this->tanggal = today()->format('Y-m-d');
        $this->selectedTanggal = today();
        $this->loadPresensi();
    }

    public function updatedTanggal($value)
    {
        $this->selectedTanggal = $value ? \Carbon\Carbon::parse($value) : today();
        $this->loadPresensi();
    }

    public function loadPresensi()
    {
        $this->presensiList = Presensi::whereDate('tanggal', $this->selectedTanggal)
            ->with('user')
            ->when($this->filterKelas, fn($q) => $q->whereHas('user', fn($q) => $q->where('kelas', $this->filterKelas)))
            ->when($this->cari, fn($q) => $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->cari}%")
                ->orWhere('kelas', 'like', "%{$this->cari}%")))
            ->latest()
            ->get();
    }

    public function updatedCari()
    {
        $this->loadPresensi();
    }

    public function updatedFilterKelas()
    {
        $this->loadPresensi();
    }

    public function lihatSiswa($userId)
    {
        $this->detailSiswa = Presensi::where('user_id', $userId)->with('user')->latest('tanggal')->limit(30)->get();
    }

    public function tutupDetail()
    {
        $this->detailSiswa = null;
    }
}; ?>

<div>
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <h1 class="text-white text-lg font-extrabold uppercase">Presensi Siswa</h1>
        <p class="text-white/70 text-xs font-bold">Rekap presensi harian</p>
    </div>

    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex items-center gap-3">
                <x-input-label value="Tanggal" />
                <input type="date" wire:model.live="tanggal" value="{{ $tanggal }}" class="border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
            </div>
            <div class="flex items-center gap-3 flex-1">
                <div class="bg-dark border-3 border-dark p-2 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="cari" placeholder="Cari nama atau kelas..." class="flex-1 border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                @if ($cari)
                    <button wire:click="$set('cari', '')" class="bg-red-500 text-white border-3 border-dark px-3 py-2 text-[10px] font-bold uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Reset</button>
                @endif
            </div>
            <div>
                <select wire:model.live="filterKelas" class="border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                    <option value="">Semua Kelas</option>
                    <optgroup label="TSM">
                        <option value="TSM A">TSM A</option>
                        <option value="TSM B">TSM B</option>
                        <option value="TSM C">TSM C</option>
                    </optgroup>
                    <optgroup label="TKR">
                        <option value="TKR A">TKR A</option>
                        <option value="TKR B">TKR B</option>
                        <option value="TKR C">TKR C</option>
                        <option value="TKR D">TKR D</option>
                    </optgroup>
                    <option value="PBS">PBS</option>
                    <option value="RPL">RPL</option>
                    <option value="DPIB">DPIB</option>
                    <option value="Animasi">Animasi</option>
                </select>
            </div>
        </div>
    </div>

    @if ($detailSiswa)
        {{-- Detail Siswa --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-extrabold text-dark uppercase text-sm">
                    Riwayat: {{ $detailSiswa->first()->user->name ?? 'Siswa' }}
                </h2>
                <button wire:click="tutupDetail" class="text-xs font-bold text-red-500 underline underline-offset-2">Tutup</button>
            </div>
            <div class="space-y-2">
                @foreach ($detailSiswa as $p)
                    <div class="flex items-center justify-between border-2 border-dark/20 p-3">
                        <div>
                            <span class="font-bold text-sm">{{ $p->tanggal->format('d M Y') }}</span>
                            <div class="flex gap-2 mt-1">
                                @if ($p->check_in)
                                    <span class="text-[10px] font-bold bg-accent/30 px-1.5 py-0.5 border border-accent">IN {{ $p->check_in->format('H:i') }}</span>
                                @endif
                                @if ($p->check_out)
                                    <span class="text-[10px] font-bold bg-highlight px-1.5 py-0.5 border border-highlight">OUT {{ $p->check_out->format('H:i') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @if ($p->foto_check_in)
                                <a href="{{ asset('storage/' . $p->foto_check_in) }}" target="_blank" class="text-[10px] font-bold text-secondary underline">Foto IN</a>
                            @endif
                            @if ($p->foto_check_out)
                                <a href="{{ asset('storage/' . $p->foto_check_out) }}" target="_blank" class="text-[10px] font-bold text-secondary underline ml-2">Foto OUT</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- Presensi Hari Ini --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4">
                Presensi Tanggal {{ $selectedTanggal->format('d/m/Y') }}
            </h2>

            @if ($presensiList->isEmpty())
                <div class="text-center py-8">
                    <p class="font-bold text-dark/50">Belum ada data presensi untuk tanggal ini</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($presensiList as $p)
                        <div class="bg-white border-3 border-dark shadow-[4px_4px_0px_0px_#1a1a1a] p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm shrink-0">
                                    {{ substr($p->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-sm truncate">{{ $p->user->name }}</p>
                                    <p class="text-[10px] font-semibold text-dark/50 uppercase">{{ $p->user->kelas }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="bg-accent/20 border-2 border-accent p-2 text-center">
                                    <p class="text-[9px] font-extrabold uppercase text-dark/60">Check In</p>
                                    <p class="font-extrabold text-sm">
                                        @if ($p->check_in)
                                            {{ $p->check_in->format('H:i') }}
                                        @else
                                            <span class="text-dark/30">-</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="bg-highlight border-2 border-highlight p-2 text-center">
                                    <p class="text-[9px] font-extrabold uppercase text-dark/60">Check Out</p>
                                    <p class="font-extrabold text-sm">
                                        @if ($p->check_out)
                                            {{ $p->check_out->format('H:i') }}
                                        @else
                                            <span class="text-dark/30">-</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-3 pt-3 border-t-3 border-dark/10">
                                @if ($p->foto_check_in)
                                    <a href="{{ asset('storage/' . $p->foto_check_in) }}" target="_blank" class="flex-1 bg-secondary text-white border-2 border-dark py-1.5 flex items-center justify-center gap-1 text-[10px] font-bold uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        In
                                    </a>
                                @endif
                                @if ($p->foto_check_out)
                                    <a href="{{ asset('storage/' . $p->foto_check_out) }}" target="_blank" class="flex-1 bg-secondary text-white border-2 border-dark py-1.5 flex items-center justify-center gap-1 text-[10px] font-bold uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Out
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
