<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public $presensiHariIni = null;
    public $totalTugas = 0;
    public $tugasSelesai = 0;

    public function mount()
    {
        $this->presensiHariIni = auth()->user()->presensis()->whereDate('tanggal', today())->first();
        $this->totalTugas = \App\Models\Tugas::where('deadline', '>=', now())->count();
        $this->tugasSelesai = auth()->user()->pengumpulanTugas()->count();
    }
}; ?>

<div class="p-4 md:p-0">
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] -mx-4 md:-mx-0 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/80 text-xs font-bold uppercase tracking-wider">Selamat datang,</p>
                <h1 class="text-white text-xl font-extrabold uppercase tracking-tight">{{ auth()->user()->name }}</h1>
                <p class="text-white/70 text-xs font-bold mt-1">{{ auth()->user()->kelas }} • {{ auth()->user()->nis }}</p>
            </div>
            <div class="bg-highlight border-3 border-dark px-3 py-2 text-center">
                <span class="block text-xs font-bold text-dark uppercase">{{ now()->format('d M') }}</span>
            </div>
        </div>
    </div>

    {{-- Presensi Card --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-extrabold text-dark uppercase text-sm">Presensi Hari Ini</h2>
            <div class="w-3 h-3 rounded-full {{ $presensiHariIni ? 'bg-accent' : 'bg-red-400' }} border-2 border-dark"></div>
        </div>
        @if ($presensiHariIni)
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <span class="bg-accent border-2 border-dark px-2 py-0.5 text-xs font-bold">CHECK IN</span>
                    <span class="font-bold text-sm">{{ $presensiHariIni->check_in ? $presensiHariIni->check_in->format('H:i') : '-' }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-highlight border-2 border-dark px-2 py-0.5 text-xs font-bold">CHECK OUT</span>
                    <span class="font-bold text-sm">{{ $presensiHariIni->check_out ? $presensiHariIni->check_out->format('H:i') : 'Belum check out' }}</span>
                </div>
            </div>
        @else
            <p class="text-sm font-semibold text-dark/60">Belum melakukan presensi hari ini</p>
        @endif
        @if (!$presensiHariIni)
            <a href="{{ route('siswa.presensi') }}" wire:navigate class="mt-3 inline-block bg-secondary text-white border-3 border-dark px-4 py-1.5 text-xs font-bold uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
                Presensi Sekarang
            </a>
        @endif
    </div>

    {{-- Tugas Card --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-3">Ringkasan Tugas</h2>
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-highlight border-3 border-dark p-3 text-center">
                <span class="block text-2xl font-extrabold text-dark">{{ $totalTugas }}</span>
                <span class="text-[10px] font-bold uppercase text-dark/70">Total Tugas</span>
            </div>
            <div class="bg-accent border-3 border-dark p-3 text-center">
                <span class="block text-2xl font-extrabold text-dark">{{ $tugasSelesai }}</span>
                <span class="text-[10px] font-bold uppercase text-dark/70">Selesai</span>
            </div>
        </div>
        <a href="{{ route('siswa.tugas') }}" wire:navigate class="mt-3 inline-block bg-primary text-white border-3 border-dark px-4 py-1.5 text-xs font-bold uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
            Lihat Tugas
        </a>
    </div>

    {{-- Logout Mobile --}}
    <div class="mt-5 md:hidden">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-red-500 text-white border-3 border-dark py-3 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all">
                Logout
            </button>
        </form>
    </div>
</div>
