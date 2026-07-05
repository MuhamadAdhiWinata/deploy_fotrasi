<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Presensi;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public $presensiHariIni = null;
    public $riwayat;
    public $foto;
    public $mode = 'check_in';

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

    public function checkIn()
    {
        $this->validate(['foto' => 'required|image|mimes:jpg,jpeg,png|max:2048']);
        $path = $this->foto->store('presensi', 'public');

        Presensi::create([
            'user_id' => auth()->id(),
            'periode_id' => auth()->user()->periode_id,
            'tanggal' => today(),
            'check_in' => now(),
            'foto_check_in' => $path,
        ]);

        $this->foto = null;
        $this->mount();
    }

    public function checkOut()
    {
        $this->validate(['foto' => 'required|image|mimes:jpg,jpeg,png|max:2048']);
        $path = $this->foto->store('presensi', 'public');

        $this->presensiHariIni->update([
            'check_out' => now(),
            'foto_check_out' => $path,
        ]);

        $this->foto = null;
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
                        • Check Out: {{ $presensiHariIni->check_out->format('H:i') }}
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
                    <p class="text-[10px] font-semibold text-dark/50 mt-1">Upload foto (JPG/JPEG/PNG, max 10MB)</p>
                    <p wire:loading wire:target="foto" class="text-[10px] font-bold text-secondary mt-1 animate-pulse">Mengupload foto...</p>
                </div>

                @if ($foto)
                    <div class="mb-4">
                        <img src="{{ $foto->temporaryUrl() }}" class="w-32 h-32 object-cover border-3 border-dark mx-auto shadow-[3px_3px_0px_0px_#1a1a1a]">
                        <p class="text-[10px] font-bold text-accent mt-1">Upload selesai ✓</p>
                    </div>
                @endif

                <button wire:click="{{ $mode === 'check_in' ? 'checkIn' : 'checkOut' }}" 
                        wire:loading.attr="disabled"
                        wire:target="checkIn,checkOut,foto"
                        class="bg-primary text-white border-3 border-dark px-8 py-3 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all disabled:opacity-50">
                    {{ $mode === 'check_in' ? 'Check In' : 'Check Out' }}
                </button>
                <p wire:loading wire:target="checkIn,checkOut" class="text-xs font-bold text-secondary mt-2 animate-pulse">Memproses presensi...</p>
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
                    <div class="flex gap-1">
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
