<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Tugas;
use App\Models\Periode;

new #[Layout('layouts.app')] class extends Component
{
    public $totalSiswa = 0;
    public $presensiHariIni = 0;
    public $totalTugas = 0;
    public $activePeriode = null;

    public function mount()
    {
        $this->activePeriode = Periode::where('is_active', true)->first();
        $periodeId = $this->activePeriode?->id;

        $this->totalSiswa = User::where('role', 'siswa')
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->count();

        $this->presensiHariIni = Presensi::whereDate('tanggal', today())
            ->when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->count();

        $this->totalTugas = Tugas::when($periodeId, fn($q) => $q->where('periode_id', $periodeId))->count();
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <h1 class="text-white text-xl font-extrabold uppercase">Dashboard Admin</h1>
        <p class="text-white/70 text-xs font-bold">{{ now()->format('l, d F Y') }}</p>
        @if ($activePeriode)
            <p class="text-accent text-[10px] font-extrabold uppercase mt-1">Periode Aktif: {{ $activePeriode->nama }} ({{ $activePeriode->tanggal_mulai->format('d M') }} — {{ $activePeriode->tanggal_selesai->format('d M') }})</p>
        @else
            <p class="text-highlight text-[10px] font-extrabold uppercase mt-1">Belum ada periode aktif — <a href="{{ route('admin.periode') }}" wire:navigate class="underline underline-offset-2">Atur Periode</a></p>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-4">
            <div class="flex items-center gap-3">
                <div class="bg-secondary border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                </div>
                <div>
                    <span class="block text-2xl font-extrabold text-dark">{{ $totalSiswa }}</span>
                    <span class="text-[10px] font-bold uppercase text-dark/60">Total Siswa</span>
                </div>
            </div>
        </div>
        <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-4">
            <div class="flex items-center gap-3">
                <div class="bg-accent border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-2xl font-extrabold text-dark">{{ $presensiHariIni }}</span>
                    <span class="text-[10px] font-bold uppercase text-dark/60">Presensi Hari Ini</span>
                </div>
            </div>
        </div>
        <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-4">
            <div class="flex items-center gap-3">
                <div class="bg-highlight border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <span class="block text-2xl font-extrabold text-dark">{{ $totalTugas }}</span>
                    <span class="text-[10px] font-bold uppercase text-dark/60">Total Tugas</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('admin.siswa') }}" wire:navigate class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5 hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
            <div class="flex items-center gap-4">
                <div class="bg-secondary/20 border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-dark uppercase text-sm">Kelola Siswa</h3>
                    <p class="text-xs font-semibold text-dark/60">Tambah, edit, hapus data siswa</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.presensi') }}" wire:navigate class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5 hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
            <div class="flex items-center gap-4">
                <div class="bg-accent/20 border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-dark uppercase text-sm">Lihat Presensi</h3>
                    <p class="text-xs font-semibold text-dark/60">Rekap presensi seluruh siswa</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.tugas') }}" wire:navigate class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5 hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
            <div class="flex items-center gap-4">
                <div class="bg-highlight border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-dark uppercase text-sm">Kelola Tugas</h3>
                    <p class="text-xs font-semibold text-dark/60">Buat, edit tugas & lihat pengumpulan</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.periode') }}" wire:navigate class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5 hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
            <div class="flex items-center gap-4">
                <div class="bg-dark/10 border-3 border-dark p-3">
                    <svg class="w-6 h-6 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-dark uppercase text-sm">Kelola Periode</h3>
                    <p class="text-xs font-semibold text-dark/60">Atur periode event & kelola peserta</p>
                </div>
            </div>
        </a>
        <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5">
            <div class="flex items-center gap-4">
                <div class="bg-red-100 border-3 border-dark p-3">
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
                <div>
                    <h3 class="font-extrabold text-dark uppercase text-sm">Logout</h3>
                    <p class="text-xs font-semibold text-dark/60">Keluar dari aplikasi</p>
                </div>
            </div>
        </div>
    </div>
</div>
