<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;
use App\Models\KaihActivity;
use App\Models\KaihEntry;
use App\Models\KaihReflection;
use Carbon\Carbon;

new #[Layout('layouts.parent')] class extends Component
{
    public $query = '';
    public $results = [];
    public $selectedSiswa = null;
    public $presensiData = [];
    public $tugasData = [];
    public $kaihEntries = [];
    public $kaihReflections = [];
    public $kaihActivities = [];
    public $dates = [];

    public function updatedQuery()
    {
        if (strlen($this->query) < 1) {
            $this->results = [];
            return;
        }

        $this->results = User::where('role', 'siswa')
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->query}%")
                  ->orWhere('nis', 'like', "%{$this->query}%");
            })
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function pilihSiswa($nis)
    {
        $siswa = User::where('role', 'siswa')->where('nis', $nis)->first();
        if (!$siswa) return;

        $this->selectedSiswa = $siswa->toArray();
        $this->results = [];
        $this->query = '';

        $this->loadPresensi($siswa);
        $this->loadTugas($siswa);
        $this->loadKaih($siswa);
    }

    public function kembali()
    {
        $this->selectedSiswa = null;
        $this->presensiData = [];
        $this->tugasData = [];
        $this->kaihEntries = [];
        $this->kaihActivities = [];
        $this->dates = [];
    }

    protected function loadPresensi(User $siswa)
    {
        $periode = $siswa->periode;
        if (!$periode || !$periode->tanggal_mulai || !$periode->tanggal_selesai) return;

        $start = $periode->tanggal_mulai->copy();
        $end = $periode->tanggal_selesai->copy();
        $today = now();
        if ($end->greaterThan($today)) $end = $today->copy();

        $dates = [];
        while ($start->lte($end)) {
            $dates[] = $start->format('Y-m-d');
            $start->addDay();
        }
        $this->dates = $dates;

        $presensi = Presensi::where('user_id', $siswa->id)
            ->whereIn('tanggal', $dates)
            ->get()
            ->keyBy(fn($p) => $p->tanggal->format('Y-m-d'));

        $this->presensiData = [];
        foreach ($dates as $d) {
            $p = $presensi->get($d);
            $this->presensiData[$d] = $p ? [
                'check_in' => $p->check_in?->format('H:i'),
                'check_out' => $p->check_out?->format('H:i'),
            ] : null;
        }
    }

    protected function loadTugas(User $siswa)
    {
        $periode = $siswa->periode;
        $tugasList = Tugas::when($periode, fn($q) => $q->where('periode_id', $periode->id))
            ->orderBy('judul')
            ->get();

        $pengumpulan = PengumpulanTugas::where('user_id', $siswa->id)
            ->get()
            ->keyBy('tugas_id');

        $this->tugasData = [];
        foreach ($tugasList as $t) {
            $sub = $pengumpulan->get($t->id);
            $this->tugasData[] = [
                'judul' => $t->judul,
                'deadline' => $t->deadline?->format('d/m/Y H:i'),
                'status' => $sub ? ($sub->status === 'dinilai' ? 'Dinilai' : 'Terkirim') : 'Belum',
                'nilai' => $sub?->nilai,
                'submitted_at' => $sub?->submitted_at?->format('d/m/Y H:i'),
            ];
        }
    }

    protected function loadKaih(User $siswa)
    {
        $this->kaihActivities = KaihActivity::orderBy('sort_order')->get()->toArray();

        $entries = KaihEntry::where('user_id', $siswa->id)
            ->whereIn('tanggal', $this->dates)
            ->get();

        $this->kaihEntries = [];
        foreach ($entries as $e) {
            $key = $e->tanggal->format('Y-m-d') . '_' . $e->activity_key;
            $this->kaihEntries[$key] = [
                'status' => $e->status,
                'reason' => $e->reason,
                'checked_at' => $e->checked_at?->format('H:i'),
            ];
        }

        $reflections = KaihReflection::where('user_id', $siswa->id)
            ->whereIn('tanggal', $this->dates)
            ->get()
            ->keyBy(fn($r) => $r->tanggal->format('Y-m-d'));

        $this->kaihReflections = [];
        foreach ($reflections as $tgl => $r) {
            $this->kaihReflections[$tgl] = $r->content;
        }
    }

    public function getEntry($tanggal, $activityKey): ?array
    {
        $key = $tanggal . '_' . $activityKey;
        return $this->kaihEntries[$key] ?? null;
    }

    public function getGroupLabel($group): string
    {
        return match ($group) {
            'rutinitas' => 'Rutinitas Pagi',
            'ibadah' => 'Ibadah',
            'fisik' => 'Aktivitas Fisik',
            'kesehatan' => 'Kesehatan',
            'belajar' => 'Belajar',
            'sosial' => 'Sosial',
            'istirahat' => 'Istirahat',
            default => $group,
        };
    }

    protected function groupActivities(): array
    {
        $groups = [];
        foreach ($this->kaihActivities as $act) {
            $groups[$act['group']][] = $act;
        }
        return $groups;
    }

    public function getDayNames(): array
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Pantau Siswa</h1>
                <p class="text-white/70 text-xs font-bold">Pantau perkembangan siswa selama Fortasi</p>
            </div>
            <div class="bg-highlight border-3 border-dark px-3 py-2">
                <span class="font-extrabold text-dark text-xs">Fortasi</span>
            </div>
        </div>
    </div>

    @if ($selectedSiswa)
        {{-- Student Dashboard View --}}
        <button wire:click="kembali" class="mb-4 bg-white border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </button>

        {{-- Student Info Card --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-xl shrink-0">
                    {{ substr($selectedSiswa['name'], 0, 1) }}
                </div>
                <div>
                    <h2 class="font-extrabold text-dark text-lg">{{ $selectedSiswa['name'] }}</h2>
                    <p class="text-sm font-semibold text-dark/60">{{ $selectedSiswa['kelas'] }} &bull; {{ $selectedSiswa['nis'] }}</p>
                    @php $periode = \App\Models\Periode::find($selectedSiswa['periode_id']); @endphp
                    @if ($periode)
                        <p class="text-[10px] font-bold text-dark/50 mt-1">Periode: {{ $periode->nama }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Presensi Recap --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden mb-6">
            <div class="bg-dark text-white px-5 py-3">
                <h2 class="font-extrabold text-sm uppercase">Rekap Presensi</h2>
            </div>
            @if (!empty($dates))
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-dark/10 border-b-3 border-dark">
                                <th class="text-left px-4 py-3 font-extrabold uppercase">Tanggal</th>
                                <th class="text-center px-4 py-3 font-extrabold uppercase">Check In</th>
                                <th class="text-center px-4 py-3 font-extrabold uppercase">Check Out</th>
                                <th class="text-center px-4 py-3 font-extrabold uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dates as $d)
                                @php
                                    $p = $presensiData[$d] ?? null;
                                    $dayNames = $this->getDayNames();
                                    $carbon = Carbon::parse($d);
                                    $label = $dayNames[$carbon->dayOfWeek] . ', ' . $carbon->format('d/m');
                                @endphp
                                <tr class="border-b-3 border-dark/10 hover:bg-dark/5">
                                    <td class="px-4 py-3 font-bold">{{ $label }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">
                                        {{ $p ? $p['check_in'] ?? '-' : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold">
                                        {{ $p ? $p['check_out'] ?? '-' : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($p)
                                            <span class="inline-block bg-accent/20 border-2 border-accent px-2 py-0.5 text-[10px] font-bold text-dark">✓ Hadir</span>
                                        @else
                                            <span class="inline-block bg-red-100 border-2 border-red-300 px-2 py-0.5 text-[10px] font-bold text-red-500">✗ Tidak Hadir</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @php
                    $hadir = count(array_filter($presensiData));
                    $pct = count($dates) > 0 ? round(($hadir / count($dates)) * 100) : 0;
                @endphp
                <div class="border-t-3 border-dark p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <div class="h-3 bg-gray-200 border-2 border-dark">
                                <div class="h-full bg-accent transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        <span class="font-extrabold text-dark text-sm">{{ $hadir }}/{{ count($dates) }} ({{ $pct }}%)</span>
                    </div>
                </div>
            @else
                <div class="p-5 text-center">
                    <p class="text-sm font-bold text-dark/50">Tidak ada data periode</p>
                </div>
            @endif
        </div>

        {{-- Tugas Recap --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden mb-6">
            <div class="bg-dark text-white px-5 py-3">
                <h2 class="font-extrabold text-sm uppercase">Rekap Tugas</h2>
            </div>
            @if (!empty($tugasData))
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-dark/10 border-b-3 border-dark">
                                <th class="text-left px-4 py-3 font-extrabold uppercase">Tugas</th>
                                <th class="text-center px-4 py-3 font-extrabold uppercase">Deadline</th>
                                <th class="text-center px-4 py-3 font-extrabold uppercase">Status</th>
                                <th class="text-center px-4 py-3 font-extrabold uppercase">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tugasData as $t)
                                <tr class="border-b-3 border-dark/10 hover:bg-dark/5">
                                    <td class="px-4 py-3 font-bold">{{ $t['judul'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-dark/60">{{ $t['deadline'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($t['status'] === 'Dinilai')
                                            <span class="inline-block bg-accent/20 border-2 border-accent px-2 py-0.5 text-[10px] font-bold text-dark">✓ {{ $t['status'] }}</span>
                                        @elseif ($t['status'] === 'Terkirim')
                                            <span class="inline-block bg-secondary/20 border-2 border-secondary px-2 py-0.5 text-[10px] font-bold text-dark">{{ $t['status'] }}</span>
                                        @else
                                            <span class="inline-block bg-red-100 border-2 border-red-300 px-2 py-0.5 text-[10px] font-bold text-red-500">{{ $t['status'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-extrabold">
                                        {{ $t['nilai'] !== null ? $t['nilai'] : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center">
                    <p class="text-sm font-bold text-dark/50">Tidak ada data tugas</p>
                </div>
            @endif
        </div>

        {{-- 7 KAIH Recap --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden mb-6">
            <div class="bg-dark text-white px-5 py-3">
                <h2 class="font-extrabold text-sm uppercase">Rekap 7 KAIH</h2>
            </div>
            @if (!empty($dates) && !empty($kaihActivities))
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-dark/10 border-b-3 border-dark">
                                <th class="text-left px-4 py-3 font-extrabold uppercase min-w-[160px]">Kebiasaan</th>
                                @foreach ($dates as $d)
                                    @php $carbon = Carbon::parse($d); @endphp
                                    <th class="text-center px-2 py-3 font-extrabold uppercase min-w-[70px] border-r-3 border-dark/10 last:border-r-0">
                                        {{ $carbon->format('d/m') }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $groups = $this->groupActivities(); @endphp
                            @foreach ($groups as $group => $groupActivities)
                                <tr class="bg-gray-100 border-b-3 border-dark">
                                    <td colspan="{{ count($dates) + 1 }}" class="p-2 font-extrabold uppercase text-[10px] text-dark/70">
                                        {{ $this->getGroupLabel($group) }}
                                    </td>
                                </tr>
                                @foreach ($groupActivities as $activity)
                                    <tr class="border-b-3 border-dark/10 hover:bg-dark/5">
                                        <td class="px-4 py-3 font-bold">{{ $activity['label'] }}</td>
                                        @foreach ($dates as $d)
                                            @php
                                                $entry = $this->getEntry($d, $activity['key']);
                                                $isYa = $entry && $entry['status'] === 'ya';
                                                $isBelum = $entry && $entry['status'] === 'belum';
                                            @endphp
                                            <td class="px-2 py-3 text-center border-r-3 border-dark/10 last:border-r-0">
                                                @if ($isYa)
                                                    <span class="text-lg">✅</span>
                                                @elseif ($isBelum)
                                                    <span class="text-lg">❌</span>
                                                    @if ($entry['reason'])
                                                        <span class="text-[8px] font-semibold text-red-500 block max-w-[70px] leading-tight" title="{{ $entry['reason'] }}">
                                                            {{ \Illuminate\Support\Str::limit($entry['reason'], 20) }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-dark/30">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- KAIH Summary --}}
                @php
                    $totalCells = count($kaihActivities) * count($dates);
                    $completedKaih = 0;
                    foreach ($dates as $d) {
                        foreach ($kaihActivities as $act) {
                            $entry = $this->getEntry($d, $act['key']);
                            if ($entry && $entry['status'] === 'ya') $completedKaih++;
                        }
                    }
                    $kaihPct = $totalCells > 0 ? round(($completedKaih / $totalCells) * 100) : 0;
                @endphp
                <div class="border-t-3 border-dark p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <div class="h-3 bg-gray-200 border-2 border-dark">
                                <div class="h-full bg-accent transition-all" style="width: {{ $kaihPct }}%"></div>
                            </div>
                        </div>
                        <span class="font-extrabold text-dark text-sm">{{ $completedKaih }}/{{ $totalCells }} ({{ $kaihPct }}%)</span>
                    </div>
                </div>

                {{-- Reflections --}}
                @php $hasReflections = !empty(array_filter($this->kaihReflections)); @endphp
                @if ($hasReflections)
                    <div class="border-t-3 border-dark p-5">
                        <h3 class="font-extrabold text-dark uppercase text-xs mb-3">Catatan Refleksi Harian</h3>
                        <div class="space-y-2">
                            @foreach ($dates as $d)
                                @php $content = $this->kaihReflections[$d] ?? null; @endphp
                                @if ($content)
                                    <div class="border-3 border-dark/20 p-3">
                                        <p class="font-extrabold text-dark text-xs uppercase mb-1">{{ Carbon::parse($d)->format('d/m') }}</p>
                                        <p class="text-xs font-semibold text-dark/70">{{ $content }}</p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="p-5 text-center">
                    <p class="text-sm font-bold text-dark/50">Tidak ada data 7 KAIH</p>
                </div>
            @endif
        </div>

    @else
        {{-- Search View --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4">Cari Siswa</h2>
            <div class="flex items-center gap-3">
                <div class="bg-dark border-3 border-dark p-2 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live="query" placeholder="Masukkan NIS atau Nama siswa..." class="flex-1 border-3 border-dark p-3 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
            </div>
        </div>

        {{-- Search Results --}}
        @if (!empty($results))
            <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden mb-6">
                <div class="bg-dark text-white px-5 py-3">
                    <h2 class="font-extrabold text-sm uppercase">Hasil Pencarian ({{ count($results) }})</h2>
                </div>
                <div class="divide-y-3 divide-dark/10">
                    @foreach ($results as $siswa)
                        <button wire:click="pilihSiswa('{{ $siswa['nis'] }}')" class="w-full text-left p-4 hover:bg-dark/5 transition-colors flex items-center gap-3">
                            <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm shrink-0">
                                {{ substr($siswa['name'], 0, 1) }}
                            </div>
                            <div>
                                <p class="font-bold text-sm text-dark">{{ $siswa['name'] }}</p>
                                <p class="text-[10px] font-semibold text-dark/50">{{ $siswa['kelas'] }} &bull; {{ $siswa['nis'] }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @elseif (strlen($query) >= 1)
            <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 text-center">
                <p class="text-sm font-bold text-dark/50">Siswa tidak ditemukan</p>
            </div>
        @endif

        {{-- Info Card --}}
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-highlight border-3 border-dark flex items-center justify-center text-dark font-extrabold text-lg">!</div>
                <div>
                    <h2 class="font-extrabold text-dark text-sm uppercase">Informasi</h2>
                    <p class="text-[10px] font-semibold text-dark/50">Pantau perkembangan siswa tanpa login</p>
                </div>
            </div>
            <ul class="space-y-1.5 text-xs font-semibold text-dark/60">
                <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent border border-dark"></span> Rekap Presensi harian</li>
                <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-secondary border border-dark"></span> Status pengumpulan tugas</li>
                <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-primary border border-dark"></span> Catatan 7 KAIH harian</li>
            </ul>
        </div>
    @endif
</div>
