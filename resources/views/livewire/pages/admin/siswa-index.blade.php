<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Periode;
use App\Models\Tugas;
use App\Models\Presensi;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;
    public $siswaList = [];
    protected $siswaPaginator;
    public $showForm = false;
    public $editId = null;
    public $showFilters = false;
    public $tempCari = '';
    public $tempFilterKelas = '';
    public $tempFilterPeriode = '';
    public $cari = '';
    public $filterKelas = '';
    public $filterPeriode = '';
    public $hariEvent = 0;
    public $totalTugasPeriode = 0;

    // Form fields
    public $name = '';
    public $email = '';
    public $nis = '';
    public $kelas = '';
    public $password = '';
    public $periode_id = '';

    public function mount()
    {
        $active = Periode::where('is_active', true)->first();
        $this->filterPeriode = $active?->id ?? '';
        $this->hitungHariEvent();
        $this->loadSiswa();
    }

    public function updatedFilterPeriode()
    {
        $this->resetPage();
        $this->hitungHariEvent();
        $this->loadSiswa();
    }

    protected function hitungHariEvent()
    {
        $this->hariEvent = 0;
        $this->totalTugasPeriode = 0;

        if ($this->filterPeriode) {
            $periode = Periode::find($this->filterPeriode);
            if ($periode && $periode->tanggal_mulai && $periode->tanggal_selesai) {
                $start = Carbon::parse($periode->tanggal_mulai)->startOfDay();
                $end = Carbon::parse($periode->tanggal_selesai)->endOfDay();
                $today = now()->endOfDay();
                $cutoff = $today->lessThan($end) ? $today : $end;

                if ($cutoff->greaterThanOrEqualTo($start)) {
                    $this->hariEvent = $start->diffInDays($cutoff) + 1;
                }

                $this->totalTugasPeriode = Tugas::where('periode_id', $this->filterPeriode)
                    ->where(function ($q) {
                        $q->whereNull('mulai')->orWhere('mulai', '<=', now());
                    })
                    ->count();
            }
        }
    }

    public function loadSiswa()
    {
        $this->siswaPaginator = User::where('role', 'siswa')
            ->when($this->filterKelas, fn($q) => $q->where('kelas', $this->filterKelas))
            ->when($this->cari, fn($q) => $q->where(function($q) {
                $q->where('name', 'like', "%{$this->cari}%")
                  ->orWhere('nis', 'like', "%{$this->cari}%")
                  ->orWhere('kelas', 'like', "%{$this->cari}%");
            }))
            ->when($this->filterPeriode, fn($q) => $q->where('periode_id', $this->filterPeriode))
            ->withCount(['presensis', 'pengumpulanTugas'])
            ->orderBy('name')
            ->paginate(12);
        $this->siswaList = $this->siswaPaginator->items();
    }

    public function updatedCari()
    {
        $this->resetPage();
        $this->loadSiswa();
    }

    public function updatedFilterKelas()
    {
        $this->resetPage();
        $this->loadSiswa();
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'nis', 'kelas', 'password', 'editId', 'showForm', 'periode_id']);
    }

    public function buatBaru()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $siswa = User::findOrFail($id);
        $this->editId = $siswa->id;
        $this->name = $siswa->name;
        $this->email = $siswa->email;
        $this->nis = $siswa->nis;
        $this->kelas = $siswa->kelas;
        $this->periode_id = $siswa->periode_id;
        $this->password = '';
        $this->showForm = true;
    }

    public function simpan()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->editId ?? ''),
            'nis' => 'required|string|max:50|unique:users,nis,' . ($this->editId ?? ''),
            'kelas' => 'required|string|max:50',
        ];

        if (!$this->editId) {
            $rules['password'] = 'required|string|min:6';
        } elseif ($this->password) {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'nis' => $this->nis,
            'kelas' => $this->kelas,
            'role' => 'siswa',
            'periode_id' => $this->periode_id ?: null,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editId) {
            User::findOrFail($this->editId)->update($data);
        } else {
            $data['password'] = Hash::make($this->password);
            User::create($data);
        }

        $this->resetForm();
        $this->loadSiswa();
    }

    public function hapus($id)
    {
        User::findOrFail($id)->delete();
        $this->loadSiswa();
    }

    public function bukaFilter()
    {
        $this->tempCari = $this->cari;
        $this->tempFilterKelas = $this->filterKelas;
        $this->tempFilterPeriode = $this->filterPeriode;
        $this->showFilters = true;
    }

    public function terapkanFilter()
    {
        $this->cari = $this->tempCari;
        $this->filterKelas = $this->tempFilterKelas;
        $this->filterPeriode = $this->tempFilterPeriode;
        $this->showFilters = false;
        $this->resetPage();
        $this->hitungHariEvent();
        $this->loadSiswa();
    }

    public function batalFilter()
    {
        $this->showFilters = false;
    }

    public function resetFilterMobile()
    {
        $this->tempCari = '';
        $this->tempFilterKelas = '';
        $this->tempFilterPeriode = '';
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Data Siswa</h1>
                <p class="text-white/70 text-xs font-bold">Kelola data siswa</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.siswa.import') }}" wire:navigate class="bg-white text-dark border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">
                    Import
                </a>
                <button wire:click="buatBaru" class="bg-accent text-dark border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all max-lg:hidden">
                    + Tambah
                </button>
            </div>
        </div>
    </div>

    {{-- Filter button (mobile only) --}}
    <button wire:click="bukaFilter" class="lg:hidden bg-white border-3 border-dark p-2.5 w-full mb-3 font-bold text-xs uppercase flex items-center justify-center gap-2 shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Filter
    </button>

    {{-- Search & Filter (desktop only) --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-4 mb-6 hidden lg:block">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex items-center gap-3 flex-1">
                <div class="bg-dark border-3 border-dark p-2 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="cari" placeholder="Cari berdasarkan nama, NIS, atau kelas..." class="flex-1 border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
                @if ($cari)
                    <button wire:click="$set('cari', '')" class="bg-red-500 text-white border-3 border-dark px-3 py-2 text-[10px] font-bold uppercase shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Reset</button>
                @endif
            </div>
            <div>
                <select wire:model.live="filterKelas" class="border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
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
                <select wire:model.live="filterPeriode" class="border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
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
                    <input type="text" wire:model="tempCari" placeholder="Cari berdasarkan nama, NIS, atau kelas..." class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary">
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
                    <select wire:model="tempFilterPeriode" class="w-full border-3 border-dark p-2 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
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
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4">{{ $editId ? 'Edit Siswa' : 'Tambah Siswa' }}</h2>
            <form wire:submit="simpan" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label value="Nama Lengkap" />
                        <x-text-input wire:model="name" class="w-full" />
                        @error('name') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-input-label value="NIS" />
                        <x-text-input wire:model="nis" class="w-full" />
                        @error('nis') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-input-label value="Email" />
                        <x-text-input wire:model="email" class="w-full" type="email" />
                        @error('email') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-input-label value="Kelas" />
                        <select wire:model="kelas" class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                            <option value="">Pilih Kelas</option>
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
                        @error('kelas') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-input-label value="Periode" />
                        <select wire:model="periode_id" class="w-full border-3 border-dark p-2.5 text-sm font-bold shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:border-primary bg-white">
                            <option value="">Pilih Periode</option>
                            @foreach (\App\Models\Periode::latest()->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                        @error('periode_id') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label value="Password {{ $editId ? '(kosongkan jika tidak diubah)' : '' }}" />
                        <x-text-input wire:model="password" class="w-full" type="password" />
                        @error('password') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <x-primary-button type="submit">Simpan</x-primary-button>
                    <x-secondary-button type="button" wire:click="resetForm">Batal</x-secondary-button>
                </div>
            </form>
        </div>
    @endif

    {{-- Card Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($this->siswaList as $siswa)
            <div class="bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-4 hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[3px_3px_0px_0px_#1a1a1a] transition-all">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm shrink-0">
                        {{ substr($siswa->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-sm truncate">{{ $siswa->name }}</p>
                        <p class="text-[10px] font-semibold text-dark/50 uppercase">{{ $siswa->kelas }} &middot; {{ $siswa->nis }}</p>
                    </div>
                </div>
                @php
                    $pPresensi = $hariEvent > 0 ? round(($siswa->presensis_count / $hariEvent) * 100) : 0;
                    $pTugas = $totalTugasPeriode > 0 ? round(($siswa->pengumpulan_tugas_count / $totalTugasPeriode) * 100) : 0;
                @endphp
                <div class="flex gap-2 mb-3">
                    <div class="flex-1 text-center border-2 border-dark/20 px-1 py-1.5">
                        <div class="relative h-2 bg-dark/10 border border-dark mb-1">
                            <div class="absolute inset-0 bg-accent transition-all" style="width: {{ $pPresensi }}%"></div>
                        </div>
                        <span class="text-[9px] font-bold">{{ $pPresensi }}%</span>
                        <span class="text-[8px] font-semibold text-dark/50 block leading-tight">Presensi</span>
                    </div>
                    <div class="flex-1 text-center border-2 border-dark/20 px-1 py-1.5">
                        <div class="relative h-2 bg-dark/10 border border-dark mb-1">
                            <div class="absolute inset-0 bg-secondary transition-all" style="width: {{ $pTugas }}%"></div>
                        </div>
                        <span class="text-[9px] font-bold">{{ $pTugas }}%</span>
                        <span class="text-[8px] font-semibold text-dark/50 block leading-tight">Tugas</span>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-1.5 border-t-3 border-dark/10 pt-3">
                    <a href="{{ route('admin.siswa.detail', $siswa->nis) }}" wire:navigate class="bg-accent text-dark border-2 border-dark py-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Detail">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                    <button wire:click="edit({{ $siswa->id }})" class="bg-secondary text-white border-2 border-dark py-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="hapus({{ $siswa->id }})" wire:confirm="Hapus siswa ini?" class="bg-red-500 text-white border-2 border-dark py-1.5 flex items-center justify-center shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border-4 border-dark shadow-[5px_5px_0px_0px_#1a1a1a] p-8 text-center">
                <p class="font-bold text-dark/50">Belum ada data siswa</p>
            </div>
        @endforelse
    </div>

    @if ($this->siswaPaginator)
        {{ $this->siswaPaginator->links() }}
    @endif

    {{-- FAB --}}
    @if (!$showForm)
        <button wire:click="buatBaru" class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-accent border-3 border-dark shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all flex items-center justify-center text-dark font-extrabold text-2xl lg:hidden">
            +
        </button>
    @endif
</div>
