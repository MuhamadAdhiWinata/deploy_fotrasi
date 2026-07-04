<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Presensi;
use App\Models\PengumpulanTugas;
use App\Models\Periode;

new #[Layout('layouts.app')] class extends Component
{
    public $siswa;
    public $presensiList;
    public $tugasList;
    public $periodeId = '';

    public function mount($nis)
    {
        $this->siswa = User::where('role', 'siswa')->where('nis', $nis)->firstOrFail();
        $active = Periode::where('is_active', true)->first();
        $this->periodeId = $active?->id ?? $this->siswa->periode_id;
        $this->loadData();
    }

    public function loadData()
    {
        $this->presensiList = Presensi::where('user_id', $this->siswa->id)
            ->when($this->periodeId, fn($q) => $q->where('periode_id', $this->periodeId))
            ->with('user')
            ->latest('tanggal')
            ->limit(30)
            ->get();

        $this->tugasList = PengumpulanTugas::where('user_id', $this->siswa->id)
            ->with('tugas')
            ->latest()
            ->get();
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white border-4 border-dark flex items-center justify-center text-primary font-extrabold text-xl shrink-0">
                    {{ substr($siswa->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-white text-lg font-extrabold uppercase">{{ $siswa->name }}</h1>
                    <p class="text-white/70 text-xs font-bold">{{ $siswa->kelas }} &middot; {{ $siswa->nis }}</p>
                    <p class="text-white/50 text-[10px] font-semibold">{{ $siswa->email }}</p>
                </div>
            </div>
            <a href="{{ route('admin.siswa') }}" wire:navigate class="bg-white text-dark border-3 border-dark px-3 py-1.5 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Kembali</a>
        </div>
    </div>

    {{-- Presensi --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] mb-6 overflow-hidden">
        <div class="bg-dark text-white px-5 py-3">
            <h2 class="font-extrabold text-sm uppercase">Riwayat Presensi</h2>
        </div>
        <div class="p-5">
            @forelse ($presensiList as $p)
                <div class="flex items-center justify-between border-3 border-dark/10 p-3 mb-2 last:mb-0">
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
                    <div class="flex gap-2">
                        @if ($p->foto_check_in)
                            <a href="{{ asset('storage/' . $p->foto_check_in) }}" target="_blank" class="text-[10px] font-bold bg-secondary text-white border-2 border-dark px-2 py-1 uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Foto IN</a>
                        @endif
                        @if ($p->foto_check_out)
                            <a href="{{ asset('storage/' . $p->foto_check_out) }}" target="_blank" class="text-[10px] font-bold bg-secondary text-white border-2 border-dark px-2 py-1 uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Foto OUT</a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm font-bold text-dark/50 text-center py-4">Belum ada riwayat presensi</p>
            @endforelse
        </div>
    </div>

    {{-- Tugas --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden">
        <div class="bg-dark text-white px-5 py-3">
            <h2 class="font-extrabold text-sm uppercase">Pengumpulan Tugas</h2>
        </div>
        <div class="p-5">
            @forelse ($tugasList as $p)
                <div class="border-3 border-dark/10 p-4 mb-3 last:mb-0">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-sm">{{ $p->tugas->judul }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 border-2 border-dark uppercase {{ $p->status === 'dinilai' ? 'bg-accent text-dark' : 'bg-secondary text-white' }}">
                            {{ $p->status }}
                        </span>
                    </div>
                    <div class="text-xs text-dark/70 mb-2">
                        Dikirim: {{ $p->submitted_at ? $p->submitted_at->format('d M Y H:i') : '-' }}
                        @if ($p->nilai)
                            | Nilai: <span class="font-bold text-dark">{{ $p->nilai }}</span>
                        @endif
                    </div>
                    <div class="flex gap-2 items-center">
                        @if ($p->file)
                            @php
                                $ext = strtolower(pathinfo($p->file, PATHINFO_EXTENSION));
                                $url = asset('storage/' . $p->file);
                            @endphp
                            @if (in_array($ext, ['jpg', 'jpeg', 'png']))
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="Preview" class="border-2 border-dark shadow-[2px_2px_0px_0px_#1a1a1a] max-w-full h-auto max-h-20 object-cover">
                                </a>
                            @elseif ($ext === 'pdf')
                                <a href="{{ $url }}" target="_blank" class="text-[10px] font-bold bg-secondary text-white border-2 border-dark px-2 py-1 uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">PDF</a>
                            @endif
                        @endif
                        @if ($p->catatan)
                            <span class="text-[10px] font-semibold text-dark/60 italic">"{{ $p->catatan }}"</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm font-bold text-dark/50 text-center py-4">Belum ada pengumpulan tugas</p>
            @endforelse
        </div>
    </div>
</div>
