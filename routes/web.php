<?php

use App\Models\PengumpulanTugas;
use App\Models\Periode;
use App\Models\Presensi;
use App\Models\Tugas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    // Siswa routes
    Route::prefix('siswa')->name('siswa.')->group(function () {
        Volt::route('/dashboard', 'pages.siswa.dashboard')->name('dashboard');
        Volt::route('/todo', 'pages.siswa.todo')->name('todo');
        Volt::route('/presensi', 'pages.siswa.presensi')->name('presensi');
        Volt::route('/kaih', 'pages.siswa.kaih-harian')->name('kaih');
        Volt::route('/kaih/rekap', 'pages.siswa.kaih-rekap')->name('kaih.rekap');
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Volt::route('/dashboard', 'pages.admin.dashboard')->name('dashboard');
        Volt::route('/siswa', 'pages.admin.siswa-index')->name('siswa');
        Volt::route('/siswa/import', 'pages.admin.siswa-import')->name('siswa.import');
        Route::get('/siswa/export', function () {

            $periodeId = request('periode_id');
            $kelas = request('kelas');
            $cari = request('cari');

            $siswa = User::where('role', 'siswa')
                ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
                ->when($cari, fn ($q) => $q->where(function ($q) {
                    $q->where('name', 'like', "%{$cari}%")
                        ->orWhere('nis', 'like', "%{$cari}%");
                }))
                ->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))
                ->orderBy('name')
                ->get();

            $periode = $periodeId ? Periode::find($periodeId) : Periode::where('is_active', true)->first();

            $dates = [];
            $tugasList = [];
            if ($periode && $periode->tanggal_mulai && $periode->tanggal_selesai) {
                $start = $periode->tanggal_mulai->copy();
                $end = $periode->tanggal_selesai->copy();
                while ($start->lte($end)) {
                    $dates[] = $start->format('Y-m-d');
                    $start->addDay();
                }
                $tugasList = Tugas::where('periode_id', $periode->id)->orderBy('judul')->get();
            }

            $presensi = Presensi::whereIn('user_id', $siswa->pluck('id'))
                ->whereIn('tanggal', $dates)
                ->get()
                ->groupBy('user_id');

            $pengumpulan = PengumpulanTugas::whereIn('user_id', $siswa->pluck('id'))
                ->get()
                ->groupBy('user_id');

            $spreadsheet = new Spreadsheet;

            // Sheet 1: Data Siswa
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Siswa');
            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', 'NIS');
            $sheet->setCellValue('C1', 'Nama');
            $sheet->setCellValue('D1', 'Kelas');
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            $row = 2;
            foreach ($siswa as $i => $s) {
                $sheet->setCellValue('A'.$row, $i + 1);
                $sheet->setCellValue('B'.$row, $s->nis);
                $sheet->setCellValue('C'.$row, $s->name);
                $sheet->setCellValue('D'.$row, $s->kelas);
                $row++;
            }

            // Sheet 2: Rekap Presensi
            if (! empty($dates)) {
                $sheet2 = $spreadsheet->createSheet();
                $sheet2->setTitle('Rekap Presensi');
                $sheet2->setCellValue('A1', 'No');
                $sheet2->setCellValue('B1', 'NIS');
                $sheet2->setCellValue('C1', 'Nama');
                $sheet2->setCellValue('D1', 'Kelas');
                $colIdx = 4;
                $dateCols = [];
                foreach ($dates as $d) {
                    $colIdx++;
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $dateCols[$d] = $colLetter;
                    $sheet2->setCellValue($colLetter.'1', Carbon::parse($d)->format('d/m'));
                }
                $sheet2->getStyle('A1:'.$colLetter.'1')->getFont()->setBold(true);
                foreach (range('A', $colLetter) as $col) {
                    $sheet2->getColumnDimension($col)->setAutoSize(true);
                }

                $row = 2;
                foreach ($siswa as $i => $s) {
                    $sheet2->setCellValue('A'.$row, $i + 1);
                    $sheet2->setCellValue('B'.$row, $s->nis);
                    $sheet2->setCellValue('C'.$row, $s->name);
                    $sheet2->setCellValue('D'.$row, $s->kelas);

                    $userPresensi = $presensi[$s->id] ?? collect();
                    $presensiByDate = $userPresensi->keyBy(fn ($p) => $p->tanggal->format('Y-m-d'));

                    foreach ($dates as $d) {
                        $col = $dateCols[$d];
                        $p = $presensiByDate->get($d);
                        if ($p) {
                            $ci = $p->check_in?->format('H:i') ?? '-';
                            $co = $p->check_out?->format('H:i') ?? '-';
                            $sheet2->setCellValue($col.$row, "In: {$ci}\nOut: {$co}");
                            $sheet2->getStyle($col.$row)->getAlignment()->setWrapText(true);
                        } else {
                            $sheet2->setCellValue($col.$row, '—');
                        }
                    }
                    $row++;
                }
            }

            // Sheet 3: Rekap Tugas
            if ($tugasList->isNotEmpty()) {
                $sheet3 = $spreadsheet->createSheet();
                $sheet3->setTitle('Rekap Tugas');
                $sheet3->setCellValue('A1', 'No');
                $sheet3->setCellValue('B1', 'NIS');
                $sheet3->setCellValue('C1', 'Nama');
                $sheet3->setCellValue('D1', 'Kelas');
                $colIdx = 4;
                $tugasCols = [];
                foreach ($tugasList as $t) {
                    $colIdx++;
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $tugasCols[$t->id] = $colLetter;
                    $label = $t->judul;
                    if ($t->deadline && now()->greaterThan($t->deadline)) {
                        $label .= ' (L)';
                    }
                    $sheet3->setCellValue($colLetter.'1', $label);
                }
                $sheet3->getStyle('A1:'.$colLetter.'1')->getFont()->setBold(true);
                foreach (range('A', $colLetter) as $col) {
                    $sheet3->getColumnDimension($col)->setAutoSize(true);
                }

                $row = 2;
                foreach ($siswa as $i => $s) {
                    $sheet3->setCellValue('A'.$row, $i + 1);
                    $sheet3->setCellValue('B'.$row, $s->nis);
                    $sheet3->setCellValue('C'.$row, $s->name);
                    $sheet3->setCellValue('D'.$row, $s->kelas);

                    $userPengumpulan = $pengumpulan[$s->id] ?? collect();
                    $pengByTugas = $userPengumpulan->keyBy('tugas_id');

                    foreach ($tugasList as $t) {
                        $col = $tugasCols[$t->id];
                        $sub = $pengByTugas->get($t->id);
                        if ($sub) {
                            $txt = '✓ Dikumpulkan';
                            if ($sub->nilai !== null) {
                                $txt .= ' (Nilai: '.$sub->nilai.')';
                            }
                            $sheet3->setCellValue($col.$row, $txt);
                        } else {
                            $sheet3->setCellValue($col.$row, '—');
                        }
                    }
                    $row++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'export-siswa-'.now()->format('Y-m-d-His').'.xlsx';

            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            return response($content, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        })->name('siswa.export');
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

// Parent monitoring (no auth)
Volt::route('orang-tua', 'pages.parent.index')->name('orang-tua');

// Profile
Volt::route('profile', 'pages.profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
