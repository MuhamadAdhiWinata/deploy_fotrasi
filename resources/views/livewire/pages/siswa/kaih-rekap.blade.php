<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\KaihActivity;
use App\Models\KaihEntry;
use App\Models\KaihReflection;

new #[Layout('layouts.app')] class extends Component
{
    public array $days = [];
    public array $activities = [];
    public array $entries = [];
    public array $reflections = [];

    public function mount(): void
    {
        $this->loadDays();
        $this->loadActivities();
        $this->loadEntries();
        $this->loadReflections();
    }

    private function loadDays(): void
    {
        $periode = auth()->user()->periode;
        if (!$periode || !$periode->tanggal_mulai || !$periode->tanggal_selesai) {
            $this->days = [];
            return;
        }

        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $start = $periode->tanggal_mulai->copy();
        $end = $periode->tanggal_selesai->copy();

        while ($start->lte($end)) {
            $key = $start->format('Y-m-d');
            $this->days[$key] = $dayNames[$start->dayOfWeek] . '<br>' . $start->format('d/m');
            $start->addDay();
        }
    }

    private function loadActivities(): void
    {
        $this->activities = KaihActivity::orderBy('sort_order')->get()->toArray();
    }

    private function loadEntries(): void
    {
        if (empty($this->days)) return;

        $dbEntries = KaihEntry::where('user_id', auth()->id())
            ->whereIn('tanggal', array_keys($this->days))
            ->get();

        foreach ($dbEntries as $e) {
            $key = $e->tanggal->format('Y-m-d') . '_' . $e->activity_key;
            $this->entries[$key] = [
                'status' => $e->status,
                'reason' => $e->reason,
                'checked_at' => $e->checked_at?->format('H:i'),
            ];
        }
    }

    private function loadReflections(): void
    {
        if (empty($this->days)) return;

        $dbReflections = KaihReflection::where('user_id', auth()->id())
            ->whereIn('tanggal', array_keys($this->days))
            ->get();

        foreach ($dbReflections as $r) {
            $this->reflections[$r->tanggal->format('Y-m-d')] = $r->content;
        }
    }

    public function getEntry(string $tanggal, string $activityKey): ?array
    {
        $key = $tanggal . '_' . $activityKey;
        return $this->entries[$key] ?? null;
    }

    public function getGroupLabel(string $group): string
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
        foreach ($this->activities as $act) {
            $groups[$act['group']][] = $act;
        }
        return $groups;
    }
}; ?>

<div class="p-4 md:p-0 space-y-6">
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] -mx-4 md:-mx-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Rekapitulasi Mingguan</h1>
                <p class="text-white/70 text-xs font-bold">7 KAIH • {{ auth()->user()->periode?->nama ?? 'Periode Aktif' }}</p>
            </div>
            <a href="{{ route('siswa.kaih') }}" wire:navigate class="bg-highlight border-3 border-dark px-3 py-1.5 font-bold text-[10px] uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
                Kembali ke Jurnal
            </a>
        </div>
    </div>

    {{-- Matrix Table --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-dark text-white">
                        <th class="text-left p-3 font-extrabold uppercase border-r-3 border-white/20 min-w-[160px]">
                            Kebiasaan
                        </th>
                        @foreach ($days as $label)
                            <th class="p-3 font-extrabold text-center border-r-3 border-white/20 last:border-r-0 min-w-[90px]">
                                {!! $label !!}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $groups = $this->groupActivities(); @endphp
                    @foreach ($groups as $group => $groupActivities)
                        {{-- Group header row --}}
                        <tr class="bg-gray-100 border-b-3 border-dark">
                            <td colspan="{{ count($days) + 1 }}" class="p-2 font-extrabold uppercase text-[10px] text-dark/70">
                                {{ $this->getGroupLabel($group) }}
                            </td>
                        </tr>
                        {{-- Activity rows --}}
                        @foreach ($groupActivities as $activity)
                            <tr class="border-b-3 border-dark/10 hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-bold text-dark border-r-3 border-dark/10">
                                    {{ $activity['label'] }}
                                </td>
                                @foreach ($days as $tanggal => $label)
                                    @php
                                        $entry = $this->getEntry($tanggal, $activity['key']);
                                        $isYa = $entry && ($entry['status'] ?? '') === 'ya';
                                        $isBelum = $entry && ($entry['status'] ?? '') === 'belum';
                                    @endphp
                                    <td class="p-3 text-center border-r-3 border-dark/10 last:border-r-0 align-top">
                                        @if ($isYa)
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-lg">✅</span>
                                                @if ($entry['checked_at'])
                                                    <span class="text-[8px] font-bold text-dark/50">{{ $entry['checked_at'] }}</span>
                                                @endif
                                            </div>
                                        @elseif ($isBelum)
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-lg">❌</span>
                                                @if ($entry['reason'])
                                                    <span class="text-[8px] font-semibold text-red-500 max-w-[80px] leading-tight block" title="{{ $entry['reason'] }}">
                                                        {{ \Illuminate\Support\Str::limit($entry['reason'], 30) }}
                                                    </span>
                                                @endif
                                            </div>
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
    </div>

    {{-- Summary Card --}}
    @php
        $totalActivities = count($this->activities);
        $totalCells = $totalActivities * count($days);
        $completedCount = 0;
        foreach ($days as $tanggal => $_) {
            foreach ($this->activities as $act) {
                $entry = $this->getEntry($tanggal, $act['key']);
                if ($entry && ($entry['status'] ?? '') === 'ya') $completedCount++;
            }
        }
        $pct = $totalCells > 0 ? round(($completedCount / $totalCells) * 100) : 0;
    @endphp
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-3">Ringkasan</h2>
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <div class="h-4 bg-gray-200 border-3 border-dark overflow-hidden">
                    <div class="h-full bg-accent transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            <span class="font-extrabold text-dark text-sm">{{ $completedCount }}/{{ $totalCells }} ({{ $pct }}%)</span>
        </div>
    </div>

    {{-- Reflections Recap --}}
    @php $hasReflections = !empty(array_filter($this->reflections)); @endphp
    @if ($hasReflections)
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4 pb-2 border-b-3 border-dark/10">
                Catatan Refleksi Harian
            </h2>
            <div class="space-y-3">
                @foreach ($days as $tanggal => $label)
                    @php $content = $this->reflections[$tanggal] ?? null; @endphp
                    @if ($content)
                        <div class="border-3 border-dark/20 p-3">
                            <p class="font-extrabold text-dark text-xs uppercase mb-1">{!! $label !!}</p>
                            <p class="text-xs font-semibold text-dark/70">{{ $content }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Teacher Notes --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-4 pb-2 border-b-3 border-dark/10">
            Catatan Wali Kelas / Guru Pendamping MPLS
        </h2>
        <div class="border-3 border-dashed border-dark/30 p-6 text-center">
            <p class="text-xs font-semibold text-dark/40">(Kosong — catatan akan diisi oleh wali kelas)</p>
        </div>
    </div>
</div>
