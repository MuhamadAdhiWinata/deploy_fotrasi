<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;
    public $tugas;
    public $pengumpulanList = [];
    protected $pengumpulanPaginator;
    public $nilaiInput = [];
    public $selectedId = null;

    public function mount($id)
    {
        $this->tugas = Tugas::findOrFail($id);
        $this->loadPengumpulan();
    }

    public function loadPengumpulan()
    {
        $this->pengumpulanPaginator = PengumpulanTugas::where('tugas_id', $this->tugas->id)
            ->with('user')
            ->latest()
            ->paginate(10);
        $this->pengumpulanList = $this->pengumpulanPaginator->items();

        foreach ($this->pengumpulanList as $p) {
            $this->nilaiInput[$p->id] = $p->nilai ?? '';
        }
    }

    public function beriNilai($id)
    {
        $this->validate([
            "nilaiInput.$id" => 'required|integer|min:0|max:100',
        ]);

        $pengumpulan = PengumpulanTugas::findOrFail($id);
        $pengumpulan->update([
            'nilai' => $this->nilaiInput[$id],
            'status' => 'dinilai',
        ]);

        $this->loadPengumpulan();
    }

    public function lihatDetail($id)
    {
        $this->selectedId = $id;
    }

    public function tutupDetail()
    {
        $this->selectedId = null;
    }
}; ?>

<div>
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Pengumpulan Tugas</h1>
                <p class="text-white/70 text-xs font-bold mt-1">{{ $tugas->judul }}</p>
            </div>
            <a href="{{ route('admin.tugas') }}" wire:navigate class="bg-white text-dark border-3 border-dark px-3 py-1.5 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
                Kembali
            </a>
        </div>
    </div>

    {{-- Detail Tugas --}}
    <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5 mb-6">
        <p class="text-sm font-semibold text-dark/70">{{ $tugas->deskripsi }}</p>
        <div class="bg-highlight border-3 border-dark px-3 py-2 mt-3 inline-block">
            <span class="text-xs font-bold">Deadline: {{ $tugas->deadline->format('d M Y H:i') }}</span>
        </div>
    </div>

    @if ($selectedId)
        @php $detail = \App\Models\PengumpulanTugas::with('user')->find($selectedId); @endphp
        @if ($detail)
            <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-extrabold text-dark uppercase text-sm">Detail Pengumpulan</h2>
                    <button wire:click="tutupDetail" class="text-xs font-bold text-red-500 underline underline-offset-2">Tutup</button>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase text-dark/60">Siswa:</span>
                        <span class="font-bold text-sm">{{ $detail->user->name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase text-dark/60">Kelas:</span>
                        <span class="font-bold text-sm">{{ $detail->user->kelas }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase text-dark/60">Dikirim:</span>
                        <span class="font-bold text-sm">{{ $detail->submitted_at ? $detail->submitted_at->format('d M Y H:i') : '-' }}</span>
                    </div>
                    @if ($detail->file)
                        @php
                            $ext = strtolower(pathinfo($detail->file, PATHINFO_EXTENSION));
                            $url = asset('storage/' . $detail->file);
                        @endphp
                        <div>
                            @if (in_array($ext, ['jpg', 'jpeg', 'png']))
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="Preview" class="border-3 border-dark shadow-[3px_3px_0px_0px_#1a1a1a] max-w-full h-auto max-h-64 object-cover">
                                </a>
                            @elseif ($ext === 'pdf')
                                <iframe src="{{ $url }}" class="w-full h-64 border-3 border-dark shadow-[3px_3px_0px_0px_#1a1a1a]"></iframe>
                            @endif
                        </div>
                    @endif
                    @if ($detail->catatan)
                        <div>
                            <span class="text-xs font-bold uppercase text-dark/60 block mb-1">Catatan:</span>
                            <p class="text-sm font-semibold bg-surface border-3 border-dark p-3">{{ $detail->catatan }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- Daftar Pengumpulan --}}
    @if ($this->pengumpulanPaginator)
        {{ $this->pengumpulanPaginator->links() }}
    @endif
    @forelse ($this->pengumpulanList as $p)
        <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-4 mb-4 hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm shrink-0">
                        {{ substr($p->user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-sm truncate">{{ $p->user->name }}</p>
                        <p class="text-[10px] font-semibold text-dark/50 uppercase">{{ $p->user->kelas }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    <span class="text-[10px] font-bold px-2 py-1 border-2 border-dark uppercase {{ $p->status === 'dinilai' ? 'bg-accent text-dark' : 'bg-secondary text-white' }}">
                        {{ $p->status }}
                    </span>
                </div>
            </div>
            {{-- Nilai & Aksi --}}
            <div class="flex items-center gap-3 pt-3 border-t-3 border-dark/10">
                <form wire:submit="beriNilai({{ $p->id }})" class="flex items-center gap-1 flex-1">
                    <input type="number" wire:model="nilaiInput.{{ $p->id }}" min="0" max="100" class="w-16 border-3 border-dark p-1.5 text-xs font-bold text-center shadow-[2px_2px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary" placeholder="0-100">
                    <button type="submit" class="bg-accent text-dark border-3 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Nilai">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </form>
                <button wire:click="lihatDetail({{ $p->id }})" class="bg-secondary text-white border-3 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Detail">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
        </div>
    @empty
        <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-8 text-center">
            <p class="font-bold text-dark/50">Belum ada pengumpulan</p>
        </div>
    @endforelse
    @if ($this->pengumpulanPaginator)
        {{ $this->pengumpulanPaginator->links() }}
    @endif
</div>
