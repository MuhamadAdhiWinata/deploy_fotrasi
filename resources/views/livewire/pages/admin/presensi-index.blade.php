<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Presensi;
use App\Models\User;
use App\Models\Periode;
use App\Services\GpsService;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;
    public $presensiList = [];
    protected $presensiPaginator;
    public $tanggal;
    public $selectedTanggal;
    public $detailSiswa = null;
    public $cari = '';
    public $filterKelas = '';
    public $periodeId = '';
    public $filterKeamanan = '';
    public $showFilters = false;
    public $tempTanggal;
    public $tempCari = '';
    public $tempFilterKelas = '';
    public $tempPeriodeId = '';
    public $tempFilterKeamanan = '';

    public function mount()
    {
        $this->tanggal = today()->format('Y-m-d');
        $this->selectedTanggal = today();
        $active = Periode::where('is_active', true)->first();
        $this->periodeId = $active?->id ?? '';
        $this->loadPresensi();
    }

    public function updatedTanggal($value)
    {
        $this->resetPage();
        $this->selectedTanggal = $value ? \Carbon\Carbon::parse($value) : today();
        $this->loadPresensi();
    }

    public function loadPresensi()
    {
        $query = Presensi::whereDate('tanggal', $this->selectedTanggal)
            ->with('user')
            ->when($this->filterKelas, fn($q) => $q->whereHas('user', fn($q) => $q->where('kelas', $this->filterKelas)))
            ->when($this->cari, fn($q) => $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->cari}%")
                ->orWhere('kelas', 'like', "%{$this->cari}%")))
            ->when($this->periodeId, fn($q) => $q->where('periode_id', $this->periodeId));

        if ($this->filterKeamanan === 'aman') {
            $query->where('lokasi_valid', true);
        } elseif ($this->filterKeamanan === 'exif_only') {
            $query->where('lokasi_valid', true)
                  ->whereNull('lat_check_in')
                  ->whereNotNull('exif_lat_in');
        } elseif ($this->filterKeamanan === 'mencurigakan') {
            $query->where(function ($q) {
                $q->where('lokasi_valid', false)
                  ->orWhereNull('lokasi_valid')
                  ->orWhere('gps_accuracy_in', '>', 50)
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('exif_lat_in')
                          ->whereNotNull('lat_check_in')
                          ->whereRaw('ABS(lat_check_in - exif_lat_in) > 0.001 OR ABS(lng_check_in - exif_lng_in) > 0.001');
                  });
            });
        }

        $this->presensiPaginator = $query->latest()->paginate(12);
        $this->presensiList = $this->presensiPaginator->items();
    }

    public function updatedCari()
    {
        $this->resetPage();
        $this->loadPresensi();
    }

    public function updatedFilterKelas()
    {
        $this->resetPage();
        $this->loadPresensi();
    }

    public function updatedPeriodeId()
    {
        $this->resetPage();
        $this->loadPresensi();
    }

    public function updatedFilterKeamanan()
    {
        $this->resetPage();
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

    public function bukaFilter()
    {
        $this->tempTanggal = $this->tanggal;
        $this->tempCari = $this->cari;
        $this->tempFilterKelas = $this->filterKelas;
        $this->tempPeriodeId = $this->periodeId;
        $this->tempFilterKeamanan = $this->filterKeamanan;
        $this->showFilters = true;
    }

    public function terapkanFilter()
    {
        $this->tanggal = $this->tempTanggal;
        $this->selectedTanggal = $this->tempTanggal ? \Carbon\Carbon::parse($this->tempTanggal) : today();
        $this->cari = $this->tempCari;
        $this->filterKelas = $this->tempFilterKelas;
        $this->periodeId = $this->tempPeriodeId;
        $this->filterKeamanan = $this->tempFilterKeamanan;
        $this->showFilters = false;
        $this->resetPage();
        $this->loadPresensi();
    }

    public function batalFilter()
    {
        $this->showFilters = false;
    }

    public function resetFilterMobile()
    {
        $this->tempTanggal = today()->format('Y-m-d');
        $this->tempCari = '';
        $this->tempFilterKelas = '';
        $this->tempPeriodeId = '';
        $this->tempFilterKeamanan = '';
    }

    private function badgeKeamanan($p): array
    {
        $flags = [];

        if ($p->lokasi_valid === null && $p->lat_check_in === null && $p->exif_lat_in === null) {
            $flags[] = 'no_gps';
        } elseif ($p->lokasi_valid === false) {
            $flags[] = 'luar_jangkauan';
        } elseif ($p->lat_check_in === null && $p->exif_lat_in !== null) {
            $flags[] = 'exif_only';
        }

        if ($p->gps_accuracy_in !== null && $p->gps_accuracy_in > 50) {
            $flags[] = 'akurasi_rendah';
        }

        if ($p->exif_lat_in !== null && $p->lat_check_in !== null) {
            $jarakExif = GpsService::hitungJarak($p->lat_check_in, $p->lng_check_in, $p->exif_lat_in, $p->exif_lng_in);
            if ($jarakExif > 100) {
                $flags[] = 'exif_conflict';
            }
        } elseif ($p->lat_check_in !== null && $p->exif_lat_in === null) {
            $flags[] = 'exif_hilang';
        }

        return GpsService::labelKeamanan($flags);
    }

    private function jarakSekolah($p, $periode): ?float
    {
        if (!$periode || !$periode->latitude) {
            return null;
        }
        $lat = $p->lat_check_in ?? $p->exif_lat_in;
        $lng = $p->lng_check_in ?? $p->exif_lng_in;
        if ($lat === null) {
            return null;
        }
        return round(GpsService::hitungJarak($lat, $lng, $periode->latitude, $periode->longitude), 1);
    }
}; ?>

<div>
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <h1 class="text-white text-lg font-extrabold uppercase">Presensi Siswa</h1>
        <p class="text-white/70 text-xs font-bold">Rekap presensi harian</p>
    </div>

    {{-- Filter button (mobile only) --}}
    <button wire:click="bukaFilter" class="lg:hidden bg-white border-3 border-dark p-2.5 w-full mb-3 font-bold text-xs uppercase flex items-center justify-center gap-2 shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Filter
    </button>

    {{-- Search & Filter (desktop only) --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4 mb-6 hidden lg:block">
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
            <div>
                <select wire:model.live="periodeId" class="border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                    <option value="">Semua Periode</option>
                    @foreach (\App\Models\Periode::latest()->get() as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="filterKeamanan" class="border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                    <option value="">Semua Status</option>
                    <option value="aman">🟢 Aman</option>
                    <option value="exif_only">🟡 EXIF Only</option>
                    <option value="mencurigakan">⚠️ Mencurigakan</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Filter Overlay (mobile) --}}
    @if ($showFilters)
        <div class="fixed inset-0 z-40 bg-black/50 lg:hidden" wire:click="batalFilter"></div>
        <div class="fixed inset-0 z-50 bg-white border-4 border-dark overflow-y-auto lg:hidden">
            <div class="bg-primary border-b-4 border-dark p-4 flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-extrabold text-sm uppercase flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </h2>
                <button wire:click="batalFilter" class="text-white font-bold text-xs border-2 border-white px-2 py-1 hover:bg-white hover:text-primary transition-colors">Tutup</button>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <x-input-label value="Tanggal" />
                    <input type="date" wire:model="tempTanggal" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                </div>
                <div>
                    <x-input-label value="Cari" />
                    <input type="text" wire:model="tempCari" placeholder="Cari nama atau kelas..." class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                </div>
                <div>
                    <x-input-label value="Kelas" />
                    <select wire:model="tempFilterKelas" class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
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
                <div>
                    <x-input-label value="Periode" />
                    <select wire:model="tempPeriodeId" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                        <option value="">Semua Periode</option>
                        @foreach (\App\Models\Periode::latest()->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Status Keamanan" />
                    <select wire:model="tempFilterKeamanan" class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                        <option value="">Semua Status</option>
                        <option value="aman">🟢 Aman</option>
                        <option value="exif_only">🟡 EXIF Only</option>
                        <option value="mencurigakan">⚠️ Mencurigakan</option>
                    </select>
                </div>
            </div>
            <div class="border-t-4 border-dark p-4 flex gap-3 sticky bottom-0 bg-white">
                <button wire:click="resetFilterMobile" class="flex-1 bg-red-500 text-white border-3 border-dark py-3 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                    Reset
                </button>
                <button wire:click="terapkanFilter" class="flex-1 bg-accent text-dark border-3 border-dark py-3 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                    Terapkan
                </button>
            </div>
        </div>
    @endif

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
                    @php
                        $periodeItem = $p->periode_id ? \App\Models\Periode::find($p->periode_id) : null;
                        $badge = $this->badgeKeamanan($p);
                        $jarak = $this->jarakSekolah($p, $periodeItem);
                    @endphp
                    <div class="border-2 border-dark/20 p-3">
                        <div class="flex items-center justify-between">
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
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold {{ $badge['warna'] }} text-white px-1.5 py-0.5 border-2 border-dark uppercase">{{ $badge['label'] }}</span>
                            </div>
                        </div>
                        @if ($p->lat_check_in || $p->exif_lat_in)
                            <div class="mt-2 grid grid-cols-2 gap-2 text-[10px] font-semibold text-dark/60">
                                @if ($p->lat_check_in)
                                    <div>
                                        <span class="block">GPS IN: {{ $p->lat_check_in }}, {{ $p->lng_check_in }}</span>
                                        <span class="block">Akurasi: {{ $p->gps_accuracy_in ?? '-' }}m</span>
                                        <a href="https://www.google.com/maps?q={{ $p->lat_check_in }},{{ $p->lng_check_in }}" target="_blank" class="text-secondary underline">Lihat Peta IN</a>
                                    </div>
                                @endif
                                @if ($p->exif_lat_in)
                                    <div>
                                        <span class="block">EXIF IN: {{ $p->exif_lat_in }}, {{ $p->exif_lng_in }}</span>
                                        <a href="https://www.google.com/maps?q={{ $p->exif_lat_in }},{{ $p->exif_lng_in }}" target="_blank" class="text-secondary underline">Lihat Peta EXIF</a>
                                    </div>
                                @endif
                                @if ($jarak !== null)
                                    <div class="col-span-2">
                                        <span class="block">Jarak sekolah: {{ $jarak }}m</span>
                                        <span class="block">IP: {{ $p->ip_address ?? '-' }}</span>
                                    </div>
                                @endif
                                @if ($p->check_out && $p->lat_check_out)
                                    <div class="col-span-2 border-t border-dark/10 pt-1 mt-1">
                                        <span class="block">GPS OUT: {{ $p->lat_check_out }}, {{ $p->lng_check_out }}</span>
                                        <a href="https://www.google.com/maps?q={{ $p->lat_check_out }},{{ $p->lng_check_out }}" target="_blank" class="text-secondary underline">Lihat Peta OUT</a>
                                    </div>
                                @endif
                            </div>
                        @endif
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

            @if (count($this->presensiList) === 0)
                <div class="text-center py-8">
                    <p class="font-bold text-dark/50">Belum ada data presensi untuk tanggal ini</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($this->presensiList as $p)
                        @php
                            $periodeItem = $p->periode_id ? \App\Models\Periode::find($p->periode_id) : null;
                            $badge = $this->badgeKeamanan($p);
                            $jarak = $this->jarakSekolah($p, $periodeItem);
                        @endphp
                        <div class="bg-white border-3 border-dark shadow-[4px_4px_0px_0px_#1a1a1a] p-4 relative">
                            {{-- Badge Keamanan --}}
                            <div class="absolute top-2 right-2">
                                <span class="text-[8px] font-bold {{ $badge['warna'] }} text-white px-1.5 py-0.5 border-2 border-dark uppercase">{{ $badge['label'] }}</span>
                            </div>

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
                            @if ($jarak !== null)
                                <div class="mt-2 text-center">
                                    <span class="text-[9px] font-semibold text-dark/50">Jarak: {{ $jarak }}m</span>
                                </div>
                            @endif
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
                                @if ($p->lat_check_in || $p->exif_lat_in)
                                    <a href="https://www.google.com/maps?q={{ $p->lat_check_in ?? $p->exif_lat_in }},{{ $p->lng_check_in ?? $p->exif_lng_in }}" target="_blank" class="flex-1 bg-dark text-white border-2 border-dark py-1.5 flex items-center justify-center gap-1 text-[10px] font-bold uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $p->lat_check_in ? 'GPS' : 'EXIF' }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($this->presensiPaginator)
                    {{ $this->presensiPaginator->links() }}
                @endif
            @endif
        </div>
    @endif
</div>
