<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\GeminiService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public $file;
    public $previewData = [];
    public $loading = false;
    public $success = false;
    public $error = null;
    public $importResults = [];

    public function identifikasi()
    {
        $this->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
        ]);

        $this->loading = true;
        $this->previewData = [];
        $this->error = null;

        try {
            $ext = $this->file->getClientOriginalExtension();

            if (in_array($ext, ['xlsx', 'xls'])) {
                $result = $this->parseExcel();
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $result = $this->parseWord();
            } elseif ($ext === 'pdf') {
                $result = $this->parsePdf();
            } else {
                $ai = app(GeminiService::class);
                $content = file_get_contents($this->file->getRealPath());
                $result = $ai->extractStudents($content, $this->file->getMimeType());
            }

            if (empty($result)) {
                $this->error = 'Tidak dapat mengekstrak data siswa dari file. Periksa format file.';
            } else {
                $this->previewData = $result;
            }
        } catch (\Exception $e) {
            $this->error = 'Gagal memproses file: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    protected function parseExcel(): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->file->getRealPath());
        $students = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rows = $sheet->toArray();
            if (empty($rows)) continue;

            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $colMap = $this->mapExcelColumns($header);
            $startRow = $colMap ? 1 : 0;

            foreach ($rows as $i => $row) {
                if ($i < $startRow) continue;
                $row = array_map('trim', $row);

                if ($colMap) {
                    $name = $row[$colMap['name']] ?? '';
                    $nis = $row[$colMap['nis']] ?? '';
                    $kelas = $row[$colMap['kelas']] ?? '';
                } else {
                    $name = $row[0] ?? '';
                    $nis = $row[1] ?? '';
                    $kelas = $row[2] ?? '';
                }

                if (!empty($name) && !empty($nis)) {
                    $students[] = [
                        'name' => $name,
                        'nis' => $nis,
                        'kelas' => $kelas,
                    ];
                }
            }
        }

        return $students;
    }

    protected function mapExcelColumns(array $header): ?array
    {
        $map = [];
        foreach ($header as $i => $col) {
            if (in_array($col, ['nama', 'name', 'nama lengkap', 'nama siswa'])) $map['name'] = $i;
            elseif (in_array($col, ['nis', 'nisn', 'no induk', 'nomor induk'])) $map['nis'] = $i;
            elseif (in_array($col, ['kelas', 'class', 'jurusan', 'rombel'])) $map['kelas'] = $i;
        }
        return isset($map['name'], $map['nis']) ? $map : null;
    }

    protected function parseWord(): array
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($this->file->getRealPath());
        $students = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $rowsData = [];
                    foreach ($element->getRows() as $row) {
                        $cells = [];
                        foreach ($row->getCells() as $cell) {
                            $cells[] = trim($cell->getText());
                        }
                        $rowsData[] = $cells;
                    }
                    $parsed = $this->parseTableRows($rowsData);
                    if (!empty($parsed)) {
                        $students = array_merge($students, $parsed);
                    }
                }
            }
        }

        if (empty($students)) {
            $text = $this->extractWordText($this->file->getRealPath());
            $students = $this->parseTextData($text);
        }

        return $students;
    }

    protected function parsePdf(): array
    {
        $text = $this->extractPdfText($this->file->getRealPath());
        $students = $this->parseTextData($text);

        if (empty($students)) {
            $ai = app(GeminiService::class);
            $content = file_get_contents($this->file->getRealPath());
            $students = $ai->extractStudents($content, 'application/pdf');
        }

        return $students;
    }

    protected function parseTableRows(array $rows): array
    {
        if (empty($rows)) return [];
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $colMap = $this->mapExcelColumns($header);
        $startRow = $colMap ? 1 : 0;
        $students = [];

        foreach ($rows as $i => $row) {
            if ($i < $startRow) continue;

            if ($colMap) {
                $name = trim($row[$colMap['name']] ?? '');
                $nis = trim($row[$colMap['nis']] ?? '');
                $kelas = trim($row[$colMap['kelas']] ?? '');
            } else {
                $name = trim($row[0] ?? '');
                $nis = trim($row[1] ?? '');
                $kelas = trim($row[2] ?? '');
            }

            if (!empty($name) && !empty($nis)) {
                $students[] = ['name' => $name, 'nis' => $nis, 'kelas' => $kelas];
            }
        }

        return $students;
    }

    protected function parseTextData(string $text): array
    {
        $students = [];
        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/\t+| {2,}/', $line);
            $parts = array_map('trim', $parts);
            $parts = array_filter($parts, fn($v) => $v !== '');

            if (count($parts) >= 2) {
                $name = $parts[0];
                $nis = $parts[1];
                $kelas = $parts[2] ?? '';

                if (!empty($name) && !empty($nis)) {
                    $students[] = ['name' => $name, 'nis' => $nis, 'kelas' => $kelas];
                }
            }
        }

        return $students;
    }

    protected function extractWordText($path): string
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        foreach ($element->getElements() as $el) {
                            $text .= $el->getText() . ' ';
                        }
                        $text .= "\n";
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $element->getText() . "\n";
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        foreach ($element->getRows() as $row) {
                            foreach ($row->getCells() as $cell) {
                                $text .= $cell->getText() . "\t";
                            }
                            $text .= "\n";
                        }
                    }
                }
            }
            return $text;
        } catch (\Exception $e) {
            return file_get_contents($path);
        }
    }

    protected function extractExcelText($path): string
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $text = '';
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $rowData[] = $cell->getValue();
                    }
                    $text .= implode("\t", $rowData) . "\n";
                }
            }
            return $text;
        } catch (\Exception $e) {
            return file_get_contents($path);
        }
    }

    protected function extractPdfText($path): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        } catch (\Exception $e) {
            return file_get_contents($path);
        }
    }

    public function simpanSemua()
    {
        $imported = 0;
        $errors = [];
        $this->importResults = [];

        foreach ($this->previewData as $data) {
            $name = trim($data['name'] ?? '');
            $nis = trim($data['nis'] ?? '');
            $kelas = trim($data['kelas'] ?? '');

            if (empty($name) || empty($nis)) {
                $errors[] = "Data tidak lengkap: {$name} - {$nis}";
                continue;
            }

            if (User::where('nis', $nis)->exists()) {
                $errors[] = "NIS {$nis} sudah terdaftar ({$name})";
                continue;
            }

            $email = strtolower($nis . '@fortasi.test');
            $nameParts = explode(' ', $name);
            $password = end($nameParts);

            User::create([
                'name' => $name,
                'email' => $email,
                'nis' => $nis,
                'kelas' => $kelas,
                'password' => Hash::make($password),
                'role' => 'siswa',
            ]);

            $imported++;
        }

        $this->importResults = [
            'success' => $imported,
            'errors' => $errors,
        ];

        $this->success = true;
        $this->previewData = [];
        $this->file = null;
    }

    public function resetForm()
    {
        $this->reset(['file', 'previewData', 'loading', 'success', 'error', 'importResults']);
    }
}; ?>

<div>
    <div class="bg-primary border-4 border-dark p-5 shadow-[6px_6px_0px_0px_#1a1a1a] mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-white text-lg font-extrabold uppercase">Import Siswa</h1>
                <p class="text-white/70 text-xs font-bold">Upload file untuk menambah data siswa secara massal</p>
            </div>
            <a href="{{ route('admin.siswa') }}" wire:navigate class="bg-white text-dark border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">Kembali</a>
        </div>
    </div>

    @if ($success)
        <div class="bg-accent border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-dark text-accent flex items-center justify-center font-extrabold text-lg">✓</div>
                <div>
                    <h2 class="font-extrabold text-dark text-sm uppercase">Import Berhasil</h2>
                    <p class="text-xs font-semibold text-dark/70">{{ $importResults['success'] }} siswa berhasil diimport</p>
                </div>
            </div>
            @if ($importResults['errors'])
                <div class="bg-white border-3 border-dark p-3 mt-3">
                    <p class="text-xs font-bold text-red-500 uppercase mb-1">Gagal:</p>
                    @foreach ($importResults['errors'] as $err)
                        <p class="text-xs font-semibold text-dark/70">- {{ $err }}</p>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('admin.siswa') }}" wire:navigate class="inline-block mt-4 bg-dark text-white border-3 border-dark px-4 py-2 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Lihat Data Siswa</a>
        </div>
    @else
        <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
            <h2 class="font-extrabold text-dark uppercase text-sm mb-4">Upload File</h2>
            <form wire:submit="identifikasi" class="space-y-4">
                <div>
                    <div class="border-4 border-dashed border-dark/30 p-8 text-center hover:border-dark/60 transition-colors cursor-pointer" onclick="document.getElementById('file-input').click()">
                        <div class="mb-3">
                            <svg class="w-10 h-10 mx-auto text-dark/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <p class="font-bold text-sm text-dark/60">Klik untuk upload atau drag & drop</p>
                        <p class="text-[10px] font-semibold text-dark/40 mt-1">PDF, JPG, PNG, Word, Excel (max 10MB)</p>
                    </div>
                    <input id="file-input" type="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png,.xls,.xlsx,.doc,.docx" class="hidden">
                    @error('file') <span class="text-xs font-bold text-red-500 block mt-2">{{ $message }}</span> @enderror
                    @if ($file)
                        <p class="text-xs font-bold text-dark/60 mt-2">File: {{ $file->getClientOriginalName() }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button type="submit" wire:loading.attr="disabled" class="bg-primary text-white border-3 border-dark px-6 py-2.5 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="identifikasi">Identifikasi Data</span>
                        <span wire:loading wire:target="identifikasi">Memproses...</span>
                    </button>
                    <button type="button" wire:click="resetForm" class="bg-red-500 text-white border-3 border-dark px-4 py-2.5 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Reset</button>
                </div>
            </form>
        </div>

        @if ($loading)
            <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-8 text-center">
                <div class="animate-pulse">
                    <div class="w-12 h-12 bg-secondary border-3 border-dark mx-auto mb-3 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>
                    <p class="font-bold text-sm text-dark">Sedang memproses data...</p>
                    <p class="text-xs font-semibold text-dark/50 mt-1">Harap tunggu sebentar</p>
                </div>
            </div>
        @endif

        @if ($error)
            <div class="bg-red-500 border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] p-5 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-dark text-white flex items-center justify-center font-extrabold text-sm">!</div>
                    <p class="font-bold text-white text-sm">{{ $error }}</p>
                </div>
            </div>
        @endif

        @if ($previewData)
            <div class="bg-white border-4 border-dark shadow-[6px_6px_0px_0px_#1a1a1a] overflow-hidden mb-6">
                <div class="bg-dark text-white px-5 py-3 flex items-center justify-between">
                    <h2 class="font-extrabold text-sm uppercase">Preview Data ({{ count($previewData) }} siswa)</h2>
                    <span class="text-[10px] font-bold text-accent">Preview Data</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-dark/10 border-b-3 border-dark">
                                <th class="text-left px-4 py-3 font-extrabold text-xs uppercase">No</th>
                                <th class="text-left px-4 py-3 font-extrabold text-xs uppercase">Nama</th>
                                <th class="text-left px-4 py-3 font-extrabold text-xs uppercase">NIS</th>
                                <th class="text-left px-4 py-3 font-extrabold text-xs uppercase">Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewData as $i => $data)
                                <tr class="border-b-3 border-dark/10 hover:bg-dark/5">
                                    <td class="px-4 py-3 font-bold text-dark/50">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 font-bold">{{ $data['name'] ?? '-' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $data['nis'] ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-[10px] font-bold bg-highlight border-2 border-dark px-2 py-0.5">{{ $data['kelas'] ?? '-' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-5 border-t-3 border-dark">
                    <p class="text-xs font-semibold text-dark/60 mb-3">Email akan dibuat otomatis: <span class="font-bold">nis@fortasi.test</span> &bull; Password: <span class="font-bold">nama belakang siswa</span></p>
                    <button wire:click="simpanSemua" wire:loading.attr="disabled" class="bg-accent text-dark border-3 border-dark px-6 py-2.5 font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="simpanSemua">Import {{ count($previewData) }} Siswa</span>
                        <span wire:loading wire:target="simpanSemua">Menyimpan...</span>
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>
