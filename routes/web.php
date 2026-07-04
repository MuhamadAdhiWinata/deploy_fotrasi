<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    // Siswa routes
    Route::prefix('siswa')->name('siswa.')->group(function () {
        Volt::route('/dashboard', 'pages.siswa.dashboard')->name('dashboard');
        Volt::route('/todo', 'pages.siswa.todo')->name('todo');
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Volt::route('/dashboard', 'pages.admin.dashboard')->name('dashboard');
        Volt::route('/siswa', 'pages.admin.siswa-index')->name('siswa');
        Volt::route('/siswa/import', 'pages.admin.siswa-import')->name('siswa.import');
        Volt::route('/siswa/{nis}', 'pages.admin.siswa-detail')->name('siswa.detail');
        Volt::route('/periode', 'pages.admin.periode-index')->name('periode');
        Volt::route('/presensi', 'pages.admin.presensi-index')->name('presensi');
        Volt::route('/tugas', 'pages.admin.tugas-index')->name('tugas');
        Volt::route('/tugas/{id}/pengumpulan', 'pages.admin.tugas-pengumpulan')->name('tugas.pengumpulan');
    });

    // Default dashboard - redirect based on role
    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'siswa.dashboard');
    })->name('dashboard');
});

// Profile
Volt::route('profile', 'pages.profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
