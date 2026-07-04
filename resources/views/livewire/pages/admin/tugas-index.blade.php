<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Tugas;
use App\Models\Periode;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;
    public $tugasList = [];
    protected $tugasPaginator;
    public $showForm = false;
    public $editId = null;
    public $cari = '';
    public $showFilters = false;
    public $tempCari = '';
    public $tempPeriodeId = '';
    public $judul = '';
    public $deskripsi = '';
    public $mulai = '';
    public $deadline = '';
    public $periodeId = '';

    public function mount()
    {
        $active = Periode::where('is_active', true)->first();
        $this->periodeId = $active?->id ?? '';
        $this->loadTugas();
    }

    public function loadTugas()
    {
        $this->tugasPaginator = Tugas::with('creator')
            ->when($this->cari, fn($q) => $q->where('judul', 'like', "%{$this->cari}%"))
            ->when($this->periodeId, fn($q) => $q->where('periode_id', $this->periodeId))
            ->latest()
            ->paginate(10);
        $this->tugasList = $this->tugasPaginator->items();
    }

    public function updatedCari()
    {
        $this->resetPage();
        $this->loadTugas();
    }

    public function updatedPeriodeId()
    {
        $this->resetPage();
        $this->loadTugas();
    }

    public function resetForm()
    {
        $this->reset(['judul', 'deskripsi', 'mulai', 'deadline', 'editId', 'showForm']);
    }

    public function buatBaru()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $t = Tugas::findOrFail($id);
        $this->editId = $t->id;
        $this->judul = $t->judul;
        $this->deskripsi = $t->deskripsi;
        $this->mulai = $t->mulai?->format('Y-m-d\TH:i') ?? '';
        $this->deadline = $t->deadline->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function simpan()
    {
        $this->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'mulai' => 'nullable|date',
            'deadline' => 'required|date',
        ]);

        $data = [
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'mulai' => $this->mulai ?: null,
            'deadline' => $this->deadline,
            'periode_id' => $this->periodeId ?: null,
        ];

        if ($this->editId) {
            Tugas::findOrFail($this->editId)->update($data);
        } else {
            $data['created_by'] = auth()->id();
            Tugas::create($data);
        }

        $this->resetForm();
        $this->loadTugas();
    }

    public function hapus($id)
    {
        Tugas::findOrFail($id)->delete();
        $this->loadTugas();
    }

    public function bukaFilter()
    {
        $this->tempCari = $this->cari;
        $this->tempPeriodeId = $this->periodeId;
        $this->showFilters = true;
    }

    public function terapkanFilter()
    {
        $this->cari = $this->tempCari;
        $this->periodeId = $this->tempPeriodeId;
        $this->showFilters = false;
        $this->resetPage();
        $this->loadTugas();
    }

    public function batalFilter()
    {
        $this->showFilters = false;
    }

    public function resetFilterMobile()
    {
        $this->tempCari = '';
        $this->tempPeriodeId = '';
    }
}; ?>

<div>
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Kelola Tugas</h1>
                <p class="text-white/70 text-xs font-bold">Buat & kelola tugas orientasi</p>
            </div>
            <button wire:click="buatBaru" class="bg-accent text-dark border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all max-lg:hidden">
                + Tugas
            </button>
        </div>
    </div>

    {{-- Filter button (mobile only) --}}
    <button wire:click="bukaFilter" class="lg:hidden bg-white border-3 border-dark p-2.5 w-full mb-3 font-bold text-xs uppercase flex items-center justify-center gap-2 shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Filter
    </button>

    {{-- Search (desktop only) --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4 mb-6 hidden lg:block">
        <div class="flex items-center gap-3">
            <div class="bg-dark border-3 border-dark p-2 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="cari" placeholder="Cari tugas berdasarkan judul..." class="flex-1 border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
            @if ($cari)
                <button wire:click="$set('cari', '')" class="bg-red-500 text-white border-3 border-dark px-3 py-2 text-[10px] font-bold uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Reset</button>
            @endif
            <div>
                <select wire:model.live="periodeId" class="border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                    <option value="">Semua Periode</option>
                    @foreach (\App\Models\Periode::latest()->get() as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
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
                    <x-input-label value="Cari" />
                    <input type="text" wire:model="tempCari" placeholder="Cari tugas berdasarkan judul..." class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                </div>
                <div>
                    <x-input-label value="Periode" />
                    <select wire:model="tempPeriodeId" class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                        <option value="">Semua Periode</option>
                        @foreach (\App\Models\Periode::latest()->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
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

    {{-- Form --}}
    @if ($showForm)
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4">{{ $editId ? 'Edit Tugas' : 'Buat Tugas Baru' }}</h2>
            <form wire:submit="simpan" class="space-y-3">
                <div>
                    <x-input-label value="Judul Tugas" />
                    <x-text-input wire:model="judul" class="w-full" />
                    @error('judul') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-input-label value="Deskripsi" />
                    <textarea wire:model="deskripsi" rows="4" class="w-full border-3 border-dark p-2 text-sm font-semibold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary"></textarea>
                    @error('deskripsi') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-input-label value="Mulai (opsional)" />
                    <input type="datetime-local" wire:model="mulai" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                    @error('mulai') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-input-label value="Deadline" />
                    <input type="datetime-local" wire:model="deadline" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                    @error('deadline') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-2 pt-2">
                    <x-primary-button type="submit">Simpan</x-primary-button>
                    <x-secondary-button type="button" wire:click="resetForm">Batal</x-secondary-button>
                </div>
            </form>
        </div>
    @endif

    {{-- Tugas List --}}
    <div class="space-y-4">
        @forelse ($this->tugasList as $t)
            <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-extrabold text-dark text-sm uppercase">{{ $t->judul }}</h3>
                            <span class="text-[10px] font-bold text-dark/50">oleh {{ $t->creator->name }}</span>
                        </div>
                        <p class="text-xs font-semibold text-dark/70 mb-2">{{ Str::limit($t->deskripsi, 150) }}</p>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-bold bg-highlight border-2 border-dark px-2 py-0.5">Deadline: {{ $t->deadline->format('d M Y H:i') }}</span>
                            @if ($t->mulai)
                                <span class="text-[10px] font-bold bg-secondary/20 border-2 border-dark px-2 py-0.5">Mulai: {{ $t->mulai->format('d M Y H:i') }}</span>
                            @endif
                            <a href="{{ route('admin.tugas.pengumpulan', $t->id) }}" wire:navigate class="text-[10px] font-bold text-secondary underline underline-offset-2">
                                Lihat Pengumpulan ({{ $t->pengumpulan()->count() }})
                            </a>
                        </div>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button wire:click="edit({{ $t->id }})" class="bg-secondary text-white border-2 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button wire:click="hapus({{ $t->id }})" wire:confirm="Hapus tugas ini?" class="bg-red-500 text-white border-2 border-dark p-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Hapus">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-8 text-center">
                <p class="font-bold text-dark/50">Belum ada tugas. Klik "+ Tugas" untuk membuat.</p>
            </div>
        @endforelse
        @if ($this->tugasPaginator)
            {{ $this->tugasPaginator->links() }}
        @endif

        {{-- FAB --}}
        @if (!$showForm)
            <button wire:click="buatBaru" class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-accent border-3 border-dark shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all flex items-center justify-center text-dark font-extrabold text-2xl lg:hidden">
                +
            </button>
        @endif
    </div>
</div>
