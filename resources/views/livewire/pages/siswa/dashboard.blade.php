<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Tugas;
use App\Models\Presensi;
use App\Models\Periode;
use Carbon\Carbon;

new #[Layout('layouts.app')] class extends Component
{
    public $presensiHariIni = null;
    public $totalTugas = 0;
    public $tugasSelesai = 0;
    public $persentasePresensi = 0;
    public $persentaseTugas = 0;

    public function mount()
    {
        $user = auth()->user();
        $periodeId = $user->periode_id;

        $this->presensiHariIni = $user->presensis()->whereDate('tanggal', today())->first();

        $now = now();
        $this->totalTugas = Tugas::when($periodeId, fn($q) => $q->where('periode_id', $periodeId))
            ->where(function ($q) use ($now) {
                $q->whereNull('mulai')->orWhere('mulai', '<=', $now);
            })
            ->count();
        $this->tugasSelesai = $user->pengumpulanTugas()->count();

        // Attendance percentage
        if ($periodeId) {
            $periode = Periode::find($periodeId);
            if ($periode) {
                $start = Carbon::parse($periode->tanggal_mulai)->startOfDay();
                $end = Carbon::parse($periode->tanggal_selesai)->endOfDay();
                $today = now()->endOfDay();
                $cutoff = $today->lessThan($end) ? $today : $end;

                if ($cutoff->lessThan($start)) {
                    $this->persentasePresensi = 0;
                } else {
                    $hariEvent = $start->diffInDays($cutoff) + 1;
                    $hariHadir = Presensi::where('user_id', $user->id)
                        ->whereBetween('tanggal', [$start, $cutoff])
                        ->count();

                    $this->persentasePresensi = $hariEvent > 0 ? round(($hariHadir / $hariEvent) * 100) : 0;
                }
            }
        }

        // Task percentage
        $this->persentaseTugas = $this->totalTugas > 0 ? round(($this->tugasSelesai / $this->totalTugas) * 100) : 0;
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

    {{-- Progress Cards --}}
    <div class="grid grid-cols-2 gap-4 mb-5">
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-3 h-3 rounded-full {{ $presensiHariIni ? 'bg-accent' : 'bg-red-400' }} border-2 border-dark"></div>
                <h2 class="font-extrabold text-dark uppercase text-xs">Presensi</h2>
            </div>
            <div class="relative h-3 bg-dark/10 border-2 border-dark mb-2">
                <div class="absolute inset-0 bg-accent transition-all duration-500" style="width: {{ $persentasePresensi }}%"></div>
            </div>
            <div class="flex items-center justify-between">
                <span class="font-extrabold text-dark text-sm">{{ $persentasePresensi }}%</span>
                @if ($presensiHariIni)
                    <span class="text-[10px] font-bold text-dark/50">{{ $presensiHariIni->check_in->format('H:i') }}</span>
                @else
                    <span class="text-[10px] font-bold text-red-400">Belum</span>
                @endif
            </div>
        </div>
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-3.5 h-3.5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <h2 class="font-extrabold text-dark uppercase text-xs">Tugas</h2>
            </div>
            <div class="relative h-3 bg-dark/10 border-2 border-dark mb-2">
                <div class="absolute inset-0 bg-secondary transition-all duration-500" style="width: {{ $persentaseTugas }}%"></div>
            </div>
            <div class="flex items-center justify-between">
                <span class="font-extrabold text-dark text-sm">{{ $persentaseTugas }}%</span>
                <span class="text-[10px] font-bold text-dark/50">{{ $tugasSelesai }}/{{ $totalTugas }}</span>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-3">Aksi Cepat</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('siswa.todo') }}" wire:navigate class="bg-secondary text-white border-3 border-dark p-3 text-center hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
                <span class="block text-lg font-extrabold">📋</span>
                <span class="text-[10px] font-bold uppercase">To Do</span>
            </a>
            <a href="{{ route('profile') }}" wire:navigate class="bg-highlight text-dark border-3 border-dark p-3 text-center hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
                <span class="block text-lg font-extrabold">👤</span>
                <span class="text-[10px] font-bold uppercase">Profile</span>
            </a>
        </div>
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
