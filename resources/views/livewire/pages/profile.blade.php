<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $nis = '';
    public string $kelas = '';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nis = $user->nis ?? '';
        $this->kelas = $user->kelas ?? '';
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ];

        if ($user->isSiswa()) {
            $rules['nis'] = ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)];
            $rules['kelas'] = ['required', 'string', 'max:50'];
        }

        $validated = $this->validate($rules);

        if ($user->isSiswa()) {
            $user->nis = $this->nis;
            $user->kelas = $this->kelas;
        }

        $user->name = $this->name;
        $user->email = $this->email;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated');
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'new_password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'new_password', 'new_password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        $this->dispatch('password-updated');
    }
}; ?>

<div>
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white border-4 border-dark flex items-center justify-center text-primary font-extrabold text-xl shrink-0">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Profile</h1>
                <p class="text-white/70 text-xs font-bold">Kelola data akun</p>
            </div>
        </div>
    </div>

    {{-- Profile Information --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-1">Informasi Akun</h2>
        <p class="text-xs font-semibold text-dark/60 mb-4">Perbarui data diri anda</p>

        <form wire:submit="updateProfile" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label value="Nama Lengkap" />
                    <x-text-input wire:model="name" class="w-full" />
                    @error('name') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-input-label value="Email" />
                    <x-text-input wire:model="email" class="w-full" type="email" />
                    @error('email') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                </div>
                @if (auth()->user()->isSiswa())
                    <div>
                        <x-input-label value="NIS" />
                        <x-text-input wire:model="nis" class="w-full" />
                        @error('nis') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
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
                @endif
            </div>
            <div class="flex items-center gap-3 pt-2">
                <x-primary-button type="submit">Simpan</x-primary-button>
                <x-action-message on="profile-updated" class="text-xs font-bold text-accent">Tersimpan</x-action-message>
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5">
        <h2 class="font-extrabold text-dark uppercase text-sm mb-1">Ubah Password</h2>
        <p class="text-xs font-semibold text-dark/60 mb-4">Gunakan password yang kuat dan unik</p>

        <form wire:submit="updatePassword" class="space-y-3 max-w-md">
            <div>
                <x-input-label value="Password Saat Ini" />
                <x-text-input wire:model="current_password" class="w-full" type="password" />
                @error('current_password') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <x-input-label value="Password Baru" />
                <x-text-input wire:model="new_password" class="w-full" type="password" />
                @error('new_password') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <x-input-label value="Konfirmasi Password Baru" />
                <x-text-input wire:model="new_password_confirmation" class="w-full" type="password" />
                @error('new_password_confirmation') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center gap-3 pt-2">
                <x-primary-button type="submit">Simpan Password</x-primary-button>
                <x-action-message on="password-updated" class="text-xs font-bold text-accent">Tersimpan</x-action-message>
            </div>
        </form>
    </div>
</div>
