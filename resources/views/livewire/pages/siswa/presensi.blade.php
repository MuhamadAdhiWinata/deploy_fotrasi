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

    public function checkIn($lat = null, $lng = null, $accuracy = null)
    {
        $this->validate(['foto' => 'required|image|mimes:jpg,jpeg,png|max:2048']);

        if ($lat === null || $lng === null) {
            $this->gpsError = 'Gagal mendapatkan lokasi GPS. Izinkan akses lokasi di browser dan coba lagi.';
            return;
        }

        $periode = Periode::find(auth()->user()->periode_id);
        if (!$periode || !$periode->latitude || !$periode->longitude) {
            $this->gpsError = 'Koordinator sekolah belum diatur oleh admin. Hubungi administrator.';
            return;
        }

        $exif = GpsService::ekstrakExifGps($this->foto->getRealPath());

        $keamanan = GpsService::cekKeamanan(
            $lat, $lng,
            $exif['lat'] ?? null, $exif['lng'] ?? null,
            $accuracy,
            $periode->latitude, $periode->longitude,
            $periode->radius_meters
        );

        if (in_array('exif_conflict', $keamanan['flags'])) {
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

        Presensi::create([
            'user_id' => auth()->id(),
            'periode_id' => auth()->user()->periode_id,
            'tanggal' => today(),
            'check_in' => now(),
            'foto_check_in' => $path,
            'lat_check_in' => $lat,
            'lng_check_in' => $lng,
            'gps_accuracy_in' => $accuracy,
            'exif_lat_in' => $exif['lat'] ?? null,
            'exif_lng_in' => $exif['lng'] ?? null,
            'ip_address' => request()->ip(),
            'lokasi_valid' => true,
        ]);

        $this->reset('foto', 'gpsError');
        $this->mount();
    }

    public function checkOut($lat = null, $lng = null, $accuracy = null)
    {
        $this->validate(['foto' => 'required|image|mimes:jpg,jpeg,png|max:2048']);

        if ($lat === null || $lng === null) {
            $this->gpsError = 'Gagal mendapatkan lokasi GPS. Izinkan akses lokasi di browser dan coba lagi.';
            return;
        }

        $periode = Periode::find(auth()->user()->periode_id);
        if (!$periode || !$periode->latitude || !$periode->longitude) {
            $this->gpsError = 'Koordinator sekolah belum diatur oleh admin. Hubungi administrator.';
            return;
        }

        $exif = GpsService::ekstrakExifGps($this->foto->getRealPath());

        $keamanan = GpsService::cekKeamanan(
            $lat, $lng,
            $exif['lat'] ?? null, $exif['lng'] ?? null,
            $accuracy,
            $periode->latitude, $periode->longitude,
            $periode->radius_meters
        );

        if (in_array('exif_conflict', $keamanan['flags'])) {
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

        $this->presensiHariIni->update([
            'check_out' => now(),
            'foto_check_out' => $path,
            'lat_check_out' => $lat,
            'lng_check_out' => $lng,
            'gps_accuracy_out' => $accuracy,
            'exif_lat_out' => $exif['lat'] ?? null,
            'exif_lng_out' => $exif['lng'] ?? null,
            'lokasi_valid' => true,
        ]);

        $this->reset('foto', 'gpsError');
        $this->mount();
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

                <div x-data="{ capturing: false, error: '' }">
                    <button x-on:click.prevent="
                        if (capturing) return;
                        capturing = true;
                        error = '';
                        let samples = [];
                        let ambil = (resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(
                                (pos) => {
                                    samples.push({
                                        lat: pos.coords.latitude,
                                        lng: pos.coords.longitude,
                                        acc: Math.round(pos.coords.accuracy)
                                    });
                                    if (samples.length < 3) {
                                        setTimeout(() => ambil(resolve, reject), 2000);
                                    } else {
                                        let allSame = samples.every(s => s.lat === samples[0].lat && s.lng === samples[0].lng);
                                        if (allSame) {
                                            reject('Koordinat tidak wajar (tidak ada perubahan posisi). Coba lagi.');
                                        } else if (samples.some(s => s.acc > 50)) {
                                            let avgAcc = Math.round(samples.reduce((a, b) => a + b.acc, 0) / samples.length);
                                            reject('Sinyal GPS lemah (akurasi rata-rata ' + avgAcc + 'm). Coba di area terbuka.');
                                        } else {
                                            resolve({
                                                lat: samples.reduce((a, b) => a + b.lat, 0) / 3,
                                                lng: samples.reduce((a, b) => a + b.lng, 0) / 3,
                                                accuracy: Math.max(...samples.map(s => s.acc))
                                            });
                                        }
                                    }
                                },
                                (err) => {
                                    let msg = 'Gagal mendapatkan lokasi. ';
                                    if (err.code === 1) msg += 'Izinkan akses GPS di browser Anda.';
                                    else if (err.code === 2) msg += 'Sinyal GPS tidak tersedia. Coba di area terbuka.';
                                    else if (err.code === 3) msg += 'Waktu habis. Pastikan GPS aktif dan coba lagi.';
                                    else msg += err.message;
                                    reject(msg);
                                },
                                { enableHighAccuracy: true, timeout: 10000 }
                            );
                        };
                        new Promise(ambil)
                            .then(pos => {
                                capturing = false;
                                $wire.{{ $mode === 'check_in' ? 'checkIn' : 'checkOut' }}(pos.lat, pos.lng, pos.accuracy);
                            })
                            .catch(msg => {
                                capturing = false;
                                error = msg;
                            });
                    " :disabled="capturing" class="bg-primary text-white border-3 border-dark px-8 py-3 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all disabled:opacity-50">
                        <span x-show="!capturing && !error">{{ $mode === 'check_in' ? 'Check In' : 'Check Out' }}</span>
                        <span x-show="capturing">Mendapatkan lokasi GPS (6 dtk)...</span>
                        <span x-show="!capturing && error" class="text-[10px]">Coba Lagi</span>
                    </button>
                    <div x-show="capturing" class="mt-2">
                        <div class="w-full bg-dark/10 h-2 border-2 border-dark overflow-hidden">
                            <div class="bg-secondary h-full animate-pulse" style="width: 100%"></div>
                        </div>
                        <p class="text-[10px] font-bold text-secondary mt-1 animate-pulse">Mengambil sampel GPS (3x)...</p>
                    </div>
                    <div x-show="error" class="mt-2 bg-red-500 border-3 border-dark p-2 shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-white font-bold text-xs" x-text="error"></p>
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
