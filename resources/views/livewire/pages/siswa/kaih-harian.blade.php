<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\KaihActivity;
use App\Models\KaihEntry;
use App\Models\KaihReflection;

new #[Layout('layouts.app')] class extends Component
{
    public $tanggal;
    public $activities = [];
    public $entries = [];
    public $reasons = [];
    public $reflection = '';
    public $reflectionSaved = false;

    public function mount()
    {
        $this->tanggal = today()->toDateString();
        $this->loadData();
    }

    public function gantiTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
        $this->loadData();
    }

    public function loadData()
    {
        $dbActivities = KaihActivity::orderBy('sort_order')->get();
        $this->activities = $dbActivities->toArray();
        $dbEntries = KaihEntry::where('user_id', auth()->id())
            ->where('tanggal', $this->tanggal)
            ->get()
            ->keyBy('activity_key');
        $this->entries = $dbEntries->map(function ($e) {
            return [
                'status' => $e->status,
                'reason' => $e->reason,
                'checked_at' => $e->checked_at?->format('H:i'),
            ];
        })->toArray();

        $this->reasons = [];
        foreach ($this->activities as $act) {
            $entry = $dbEntries->get($act['key']);
            if ($entry && $entry->status === 'belum') {
                $this->reasons[$act['key']] = $entry->reason ?? '';
            } else {
                $this->reasons[$act['key']] = '';
            }
        }

        $reflection = KaihReflection::where('user_id', auth()->id())
            ->where('tanggal', $this->tanggal)
            ->first();
        $this->reflection = $reflection?->content ?? '';
        $this->reflectionSaved = false;
    }

    public function toggleStatus($activityKey)
    {
        if (!$this->isToday()) return;

        $entry = KaihEntry::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'activity_key' => $activityKey,
                'tanggal' => $this->tanggal,
            ],
            [
                'status' => 'ya',
                'reason' => null,
                'checked_at' => now(),
                'periode_id' => auth()->user()->periode_id,
            ]
        );

        $this->reasons[$activityKey] = '';
        $this->loadData();
    }

    public function setBelum($activityKey)
    {
        if (!$this->isToday()) return;

        KaihEntry::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'activity_key' => $activityKey,
                'tanggal' => $this->tanggal,
            ],
            [
                'status' => 'belum',
                'reason' => '',
                'checked_at' => null,
                'periode_id' => auth()->user()->periode_id,
            ]
        );

        $this->loadData();
    }

    public function saveReason($activityKey)
    {
        if (!$this->isToday()) return;

        $reason = $this->reasons[$activityKey] ?? '';
        if (empty(trim($reason))) {
            session()->flash('error_' . $activityKey, 'Alasan wajib diisi.');
            return;
        }

        KaihEntry::where('user_id', auth()->id())
            ->where('activity_key', $activityKey)
            ->where('tanggal', $this->tanggal)
            ->update(['reason' => $reason]);

        $this->loadData();
    }

    public function simpanRefleksi()
    {
        if (!$this->isToday()) return;

        if (empty(trim($this->reflection))) {
            session()->flash('reflection_error', 'Refleksi hari ini wajib diisi.');
            return;
        }

        KaihReflection::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'tanggal' => $this->tanggal,
            ],
            [
                'content' => $this->reflection,
                'periode_id' => auth()->user()->periode_id,
            ]
        );

        $this->reflectionSaved = true;
    }

    public function getEntry($activityKey): ?array
    {
        if (isset($this->entries[$activityKey])) {
            $entry = $this->entries[$activityKey];
            if (is_array($entry)) {
                return $entry;
            }
        }
        return null;
    }

    public function getGroupLabel($group)
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

    public function getDays()
    {
        $periode = auth()->user()->periode;
        if (!$periode || !$periode->tanggal_mulai || !$periode->tanggal_selesai) {
            return [];
        }

        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $days = [];
        $start = $periode->tanggal_mulai->copy();
        $end = $periode->tanggal_selesai->copy();

        while ($start->lte($end)) {
            $key = $start->format('Y-m-d');
            $days[$key] = $dayNames[$start->dayOfWeek] . ', ' . $start->format('d/m');
            $start->addDay();
        }

        return $days;
    }

    public function isToday(): bool
    {
        return $this->tanggal === today()->toDateString();
    }

    public function groupedActivities(): array
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
                <h1 class="text-white text-lg font-extrabold uppercase">Jurnal 7 KAIH</h1>
                <p class="text-white/70 text-xs font-bold">7 Kebiasaan Anak Indonesia Hebat</p>
            </div>
            <a href="{{ route('siswa.kaih.rekap') }}" wire:navigate class="bg-highlight border-3 border-dark px-3 py-1.5 font-bold text-[10px] uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
                Rekap Mingguan
            </a>
        </div>
    </div>

    {{-- Date Navigation --}}
    @php
        $days = $this->getDays();
        $today = today()->toDateString();
    @endphp
    <div class="flex gap-1.5 overflow-x-auto pb-1 -mx-4 md:-mx-0 px-4 md:px-0 scrollbar-hide">
        @foreach ($days as $day => $label)
            @php $isToday = $day === $today; @endphp
            @if ($isToday)
                <button wire:click="gantiTanggal('{{ $day }}')"
                        class="shrink-0 px-3 py-2 border-3 border-dark font-bold text-xs uppercase transition-all {{ $tanggal === $day ? 'bg-primary text-white shadow-[3px_3px_0px_0px_#1a1a1a]' : 'bg-white text-dark shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a]' }}">
                    {{ $label }}
                </button>
            @else
                <div class="shrink-0 px-3 py-2 border-3 border-dark/30 font-bold text-xs uppercase text-dark/30 bg-gray-100 cursor-not-allowed">
                    {{ $label }}
                </div>
            @endif
        @endforeach
    </div>

    {{-- Activities List --}}
    @php $groups = $this->groupedActivities(); @endphp
    @foreach ($groups as $group => $groupActivities)
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4 pb-2 border-b-3 border-dark/10">
                {{ $this->getGroupLabel($group) }}
            </h2>
            <div class="space-y-4">
                @foreach ($groupActivities as $activity)
                    @php
                        $entry = $this->getEntry($activity['key']);
                        $isYa = $entry && ($entry['status'] ?? '') === 'ya';
                        $isBelum = $entry && ($entry['status'] ?? '') === 'belum';
                        $isUndefined = !$entry;
                        $isToday = $this->isToday();
                    @endphp
                    <div class="border-3 border-dark p-4 {{ $isYa ? 'bg-accent/10' : ($isBelum ? 'bg-red-50' : 'bg-gray-50') }}">
                        {{-- Activity header --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-extrabold text-dark text-sm {{ $isYa ? 'line-through opacity-60' : '' }}">
                                    {{ $activity['label'] }}
                                </p>
                                <p class="text-[10px] font-semibold text-dark/60 mt-0.5 {{ $isYa ? 'line-through opacity-40' : '' }}">
                                    {{ $activity['description'] }}
                                </p>
                            </div>
                            @if ($isToday)
                                <div class="flex gap-1.5 shrink-0">
                                    <button wire:click="toggleStatus('{{ $activity['key'] }}')"
                                            class="px-3 py-1.5 border-3 border-dark font-bold text-[10px] uppercase transition-all
                                            {{ $isYa ? 'bg-accent text-dark shadow-[2px_2px_0px_0px_#1a1a1a]' : 'bg-white text-dark/50 shadow-[3px_3px_0px_0px_#1a1a1a] hover:shadow-[1px_1px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5' }}">
                                        Ya ✓
                                    </button>
                                    <button wire:click="setBelum('{{ $activity['key'] }}')"
                                            class="px-3 py-1.5 border-3 border-dark font-bold text-[10px] uppercase transition-all
                                            {{ $isBelum ? 'bg-red-400 text-white shadow-[2px_2px_0px_0px_#1a1a1a]' : 'bg-white text-dark/50 shadow-[3px_3px_0px_0px_#1a1a1a] hover:shadow-[1px_1px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5' }}">
                                        Belum ✗
                                    </button>
                                </div>
                            @else
                                <div class="flex gap-1.5 shrink-0">
                                    <div class="px-3 py-1.5 border-3 border-dark/30 font-bold text-[10px] uppercase text-dark/30 bg-gray-100">
                                        Ya ✓
                                    </div>
                                    <div class="px-3 py-1.5 border-3 border-dark/30 font-bold text-[10px] uppercase text-dark/30 bg-gray-100">
                                        Belum ✗
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Timestamp when Ya --}}
                        @if ($isYa && !empty($entry['checked_at']))
                            <div class="mt-2">
                                <span class="inline-block bg-accent/20 border-2 border-accent px-2 py-0.5 text-[10px] font-bold text-dark">
                                    ✓ Dicentang {{ $entry['checked_at'] }} WIB
                                </span>
                            </div>
                        @endif

                        {{-- Reason textarea when Belum --}}
                        @if ($isBelum)
                            <div class="mt-3 border-t-3 border-red-200 pt-3">
                                <label class="text-[10px] font-bold text-red-600 block mb-1">* Alasan belum/tidak mengerjakan:</label>
                                @php
                                    $errorKey = 'error_' . $activity['key'];
                                    $flashError = session($errorKey);
                                @endphp
                                <textarea wire:model="reasons.{{ $activity['key'] }}" rows="2"
                                          class="w-full border-3 border-red-300 p-2 text-xs font-semibold shadow-[2px_2px_0px_0px_#1a1a1a] focus:outline-none focus:border-red-500"
                                          placeholder="Jelaskan alasan kenapa belum/tidak mengerjakan..."></textarea>
                                @if ($flashError)
                                    <span class="text-[10px] font-bold text-red-500 block mt-1">{{ $flashError }}</span>
                                @endif
                                <button wire:click="saveReason('{{ $activity['key'] }}')"
                                        class="mt-2 bg-red-400 text-white border-3 border-dark px-4 py-1 font-bold text-[10px] uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
                                    Simpan Alasan
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Daily Reflection --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-4 pb-2 border-b-3 border-dark/10">
            Refleksi Hari Ini
        </h2>
        <p class="text-[10px] font-semibold text-dark/50 mb-3">(Ceritakan pengalaman/kesan menerapkan 7 KAIH hari ini)</p>
        @if ($this->isToday())
            <textarea wire:model="reflection" rows="5"
                      class="w-full border-3 border-dark p-3 text-sm font-semibold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary"
                      placeholder="Tulis pengalaman dan kesanmu hari ini..."></textarea>
            @if (session('reflection_error'))
                <span class="text-xs font-bold text-red-500 block mt-1">{{ session('reflection_error') }}</span>
            @endif
            <button wire:click="simpanRefleksi"
                    class="mt-4 bg-secondary text-white border-3 border-dark px-6 py-2.5 font-bold text-xs uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all">
                Simpan Jurnal Harian
            </button>
            @if ($reflectionSaved)
                <div class="mt-3 bg-accent/20 border-3 border-accent p-3">
                    <p class="text-xs font-bold text-dark">✓ Jurnal harian berhasil disimpan!</p>
                </div>
            @endif
        @else
            <div class="border-3 border-dark/20 p-4 bg-gray-50">
                <p class="text-xs font-semibold text-dark/50 italic">
                    @if ($reflection)
                        {{ $reflection }}
                    @else
                        (Belum ada catatan refleksi untuk hari ini)
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
