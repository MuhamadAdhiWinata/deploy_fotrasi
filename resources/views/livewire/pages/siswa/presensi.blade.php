<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Presensi;
use App\Models\Periode;
use App\Services\GpsService;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public $presensiHariIni = null;
    public $riwayat;
    public $foto;
    public $mode = 'check_in';
    public $gpsError = '';

    public function mount()
    {
        $this->presensiHariIni = Presensi::where('user_id', auth()->id())
            ->whereDate('tanggal', today())
            ->first();

        if ($this->presensiHariIni) {
            $this->mode = $this->presensiHariIni->check_in && !$this->presensiHariIni->check_out ? 'check_out' : 'done';
        }

        $this->riwayat = Presensi::where('user_id', auth()->id())
            ->latest('tanggal')
            ->limit(30)
            ->get();
    }

    private function prosesPresensi(string $tipe, ?float $lat, ?float $lng, ?float $accuracy): void
    {
        $this->validate(['foto' => 'required|image|mimes:jpg,jpeg,png|max:2048']);

        $periode = Periode::find(auth()->user()->periode_id);
        if (!$periode || !$periode->latitude || !$periode->longitude) {
            $this->gpsError = 'Koordinator sekolah belum diatur oleh admin. Hubungi administrator.';
            return;
        }

        $exif = GpsService::ekstrakExifGps($this->foto->getRealPath());

        if ($lat === null && $exif === null) {
            $this->gpsError = 'Gagal mendapatkan lokasi. Pastikan GPS aktif dan foto memiliki data lokasi.';
            return;
        }

        $keamanan = GpsService::cekKeamanan(
            $lat, $lng,
            $exif['lat'] ?? null, $exif['lng'] ?? null,
            $accuracy,
            $periode->latitude, $periode->longitude,
            $periode->radius_meters
        );

        if (in_array('exif_conflict', $keamanan['flags']) && $lat !== null) {
            $this->gpsError = 'Lokasi foto tidak sesuai dengan lokasi GPS. Gunakan kamera langsung, bukan screenshot.';
            $this->reset('foto');
            return;
        }

        if ($keamanan['lokasi_valid'] !== true) {
            $this->gpsError = 'Anda berada ' . $keamanan['jarak'] . 'm dari sekolah. Presensi hanya dalam radius ' . $periode->radius_meters . 'm.';
            $this->reset('foto');
            return;
        }

        $path = $this->foto->store('presensi', 'public');

        $data = [
            'user_id' => auth()->id(),
            'periode_id' => auth()->user()->periode_id,
            'tanggal' => today(),
            'foto_check_in' => $path,
            'exif_lat_in' => $exif['lat'] ?? null,
            'exif_lng_in' => $exif['lng'] ?? null,
            'ip_address' => request()->ip(),
            'lokasi_valid' => $keamanan['lokasi_valid'],
        ];

        $data['check_in'] = now();

        if ($lat !== null) {
            $data['lat_check_in'] = $lat;
            $data['lng_check_in'] = $lng;
            $data['gps_accuracy_in'] = $accuracy;
        }

        if ($tipe === 'check_out') {
            unset($data['check_in'], $data['foto_check_in'], $data['exif_lat_in'], $data['exif_lng_in']);
            $data['check_out'] = now();
            $data['foto_check_out'] = $path;
            $data['exif_lat_out'] = $exif['lat'] ?? null;
            $data['exif_lng_out'] = $exif['lng'] ?? null;

            if ($lat !== null) {
                $data['lat_check_out'] = $lat;
                $data['lng_check_out'] = $lng;
                $data['gps_accuracy_out'] = $accuracy;
            }

            $this->presensiHariIni->update($data);
        } else {
            Presensi::create($data);
        }

        $this->reset('foto', 'gpsError');
        $this->mount();
    }

    public function checkIn($lat = null, $lng = null, $accuracy = null)
    {
        $this->prosesPresensi('check_in', $lat, $lng, $accuracy);
    }

    public function checkOut($lat = null, $lng = null, $accuracy = null)
    {
        $this->prosesPresensi('check_out', $lat, $lng, $accuracy);
    }
}; ?>

<div class="p-4 md:p-0">
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] -mx-4 md:-mx-0 mb-6">
        <h1 class="text-white text-lg font-extrabold uppercase">Presensi</h1>
        <p class="text-white/70 text-xs font-bold">{{ now()->format('l, d F Y') }}</p>
    </div>

    {{-- Presensi Card --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
        @if ($mode === 'done')
            <div class="text-center py-4">
                <div class="inline-block bg-accent border-3 border-dark px-4 py-2 mb-3">
                    <span class="font-extrabold text-lg text-dark">✓</span>
                </div>
                <p class="font-bold text-dark text-sm">Presensi selesai hari ini</p>
                <p class="text-xs font-semibold text-dark/60 mt-1">
                    Check In: {{ $presensiHariIni->check_in->format('H:i') }}
                    @if ($presensiHariIni->check_out)
                        &bull; Check Out: {{ $presensiHariIni->check_out->format('H:i') }}
                    @endif
                </p>
            </div>
        @else
            <div class="text-center py-4">
                <div class="inline-block bg-highlight border-3 border-dark px-4 py-2 mb-3">
                    <span class="font-extrabold text-lg text-dark">{{ $mode === 'check_in' ? '↗' : '↙' }}</span>
                </div>
                <h2 class="font-extrabold text-dark uppercase text-sm mb-4">
                    {{ $mode === 'check_in' ? 'Check In Masuk' : 'Check Out Pulang' }}
                </h2>

                <div class="mb-4">
                    <input type="file" wire:model="foto" accept=".jpg,.jpeg,.png" capture="environment"
                           class="block w-full text-sm font-semibold file:mr-4 file:py-2 file:px-4 file:border-3 file:border-dark file:bg-highlight file:text-dark file:font-bold file:text-xs file:uppercase file:cursor-pointer file:hover:bg-highlight/80">
                    @error('foto') <span class="text-xs font-bold text-red-500 block mt-1">{{ $message }}</span> @enderror
                    <p class="text-[10px] font-semibold text-dark/50 mt-1">Ambil foto (gunakan HP untuk kamera langsung)</p>
                    <p wire:loading wire:target="foto" class="text-[10px] font-bold text-secondary mt-1 animate-pulse">Mengupload foto...</p>
                </div>

                @if ($foto)
                    <div class="mb-4">
                        <img src="{{ $foto->temporaryUrl() }}" class="w-32 h-32 object-cover border-3 border-dark mx-auto shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-[10px] font-bold text-accent mt-1">Foto siap ✓</p>
                    </div>
                @endif

                @if ($gpsError)
                    <div class="mb-4 bg-red-500 border-3 border-dark p-3 shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-white font-bold text-xs">{{ $gpsError }}</p>
                    </div>
                @endif

                {{-- Single-step: GPS + submit in one click --}}
                <div x-data="{ loading: false, error: '' }">
                    <div x-show="loading" class="mb-3 bg-secondary border-3 border-dark p-3 shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-white font-bold text-xs">
                            <span x-text="gpsStep"></span>
                        </p>
                    </div>
                    <div x-show="error" class="mb-3 bg-red-500 border-3 border-dark p-3 shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-white font-bold text-xs" x-text="error"></p>
                    </div>

                    <button x-show="!loading"
                            x-on:click.prevent="
                                loading = true;
                                error = '';
                                gpsStep = 'Mendapatkan lokasi GPS... Izinkan akses lokasi jika diminta.';
                                navigator.geolocation.getCurrentPosition(
                                    (pos) => {
                                        gpsStep = 'GPS dapat, mengirim presensi...';
                                        $wire.{{ $mode === 'check_in' ? 'checkIn' : 'checkOut' }}(pos.coords.latitude, pos.coords.longitude, Math.round(pos.coords.accuracy));
                                    },
                                    (err) => {
                                        gpsStep = 'GPS tidak tersedia, menggunakan lokasi dari foto...';
                                        $wire.{{ $mode === 'check_in' ? 'checkIn' : 'checkOut' }}(null, null, null);
                                    },
                                    { enableHighAccuracy: true, timeout: 15000 }
                                );
                            "
                            class="bg-primary text-white border-3 border-dark px-8 py-3 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all w-full">
                        {{ $mode === 'check_in' ? 'Check In' : 'Check Out' }}
                    </button>

                    <button x-show="loading" disabled class="bg-gray-400 text-white border-3 border-dark px-8 py-3 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] opacity-50 w-full">
                        <span x-text="gpsStep"></span>
                    </button>

                    <div wire:loading wire:target="checkIn,checkOut" class="mt-2">
                        <div class="w-full bg-dark/10 h-2 border-2 border-dark overflow-hidden">
                            <div class="bg-accent h-full animate-pulse" style="width: 100%"></div>
                        </div>
                        <p class="text-[10px] font-bold text-accent mt-1 animate-pulse">Memverifikasi data...</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Riwayat --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-4">Riwayat Presensi</h2>
        <div class="space-y-2">
            @forelse ($riwayat as $p)
                <div class="flex items-center justify-between border-2 border-dark/20 p-3">
                    <div>
                        <span class="font-bold text-sm text-dark">{{ $p->tanggal->format('d M Y') }}</span>
                        <div class="flex gap-2 mt-1">
                            @if ($p->check_in)
                                <span class="text-[10px] font-bold bg-accent/30 border border-accent px-1.5 py-0.5">IN {{ $p->check_in->format('H:i') }}</span>
                            @endif
                            @if ($p->check_out)
                                <span class="text-[10px] font-bold bg-highlight border border-highlight px-1.5 py-0.5">OUT {{ $p->check_out->format('H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($p->lokasi_valid === true)
                            <span class="text-[9px] font-bold bg-green-500 text-white px-1.5 py-0.5 border-2 border-dark">GPS✓</span>
                        @elseif ($p->lokasi_valid === false)
                            <span class="text-[9px] font-bold bg-red-500 text-white px-1.5 py-0.5 border-2 border-dark">GPS✗</span>
                        @endif
                        @if ($p->foto_check_in)
                            <span class="text-[10px] font-bold text-dark/50">📸</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm font-semibold text-dark/50 text-center py-4">Belum ada riwayat presensi</p>
            @endforelse
        </div>
    </div>
</div>
