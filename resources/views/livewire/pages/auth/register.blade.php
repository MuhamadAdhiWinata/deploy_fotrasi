<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $nis = '';
    public string $kelas = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'nis' => ['required', 'string', 'max:50', 'unique:'.User::class],
            'kelas' => ['required', 'string', 'max:50'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'siswa';

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('siswa.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register" class="space-y-4">
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full border-3 border-dark" type="text" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nis" :value="__('NIS')" />
            <x-text-input wire:model="nis" id="nis" class="block mt-1 w-full border-3 border-dark" type="text" required />
            <x-input-error :messages="$errors->get('nis')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kelas" :value="__('Kelas')" />
            <x-text-input wire:model="kelas" id="kelas" class="block mt-1 w-full border-3 border-dark" type="text" required placeholder="Contoh: X-A" />
            <x-input-error :messages="$errors->get('kelas')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full border-3 border-dark" type="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full border-3 border-dark" type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full border-3 border-dark" type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm font-bold text-dark underline underline-offset-4 hover:text-primary" href="{{ route('login') }}" wire:navigate>
                Sudah punya akun?
            </a>
            <button type="submit" class="bg-primary text-white border-3 border-dark px-6 py-2.5 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all">
                Daftar
            </button>
        </div>
    </form>
</div>
