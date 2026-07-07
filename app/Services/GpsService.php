<?php

namespace App\Services;

class GpsService
{
    public static function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public static function dalamRadius(float $userLat, float $userLng, float $schoolLat, float $schoolLng, int $radiusMeters): bool
    {
        $jarak = self::hitungJarak($userLat, $userLng, $schoolLat, $schoolLng);

        return $jarak <= $radiusMeters;
    }

    public static function exifToDecimal(array $exifCoord, string $ref): float
    {
        $degrees = count($exifCoord) > 0 ? self::exifGpsToFloat($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? self::exifGpsToFloat($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? self::exifGpsToFloat($exifCoord[2]) : 0;

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal *= -1;
        }

        return round($decimal, 7);
    }

    private static function exifGpsToFloat($value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            $parts = explode('/', $value);
            if (count($parts) === 2) {
                return (float) $parts[0] / (float) $parts[1];
            }

            return (float) $value;
        }

        if (is_array($value) && count($value) === 2) {
            return (float) $value[0] / (float) $value[1];
        }

        return 0;
    }

    public static function ekstrakExifGps(string $filePath): ?array
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $exif = @exif_read_data($filePath, 'EXIF');

        if ($exif === false) {
            return null;
        }

        if (! isset($exif['GPSLatitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitude'], $exif['GPSLongitudeRef'])) {
            return null;
        }

        $lat = self::exifToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        $lng = self::exifToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

        return [
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    public static function cekKeamanan(?float $gpsLat, ?float $gpsLng, ?float $exifLat, ?float $exifLng, ?float $accuracy, ?float $schoolLat, ?float $schoolLng, int $radius): array
    {
        $flags = [];
        $lokasiValid = null;

        if ($gpsLat === null || $gpsLng === null) {
            $flags[] = 'no_gps';

            return [
                'lokasi_valid' => null,
                'flags' => $flags,
                'jarak' => null,
                'exif_cocok' => null,
            ];
        }

        $jarak = self::hitungJarak($gpsLat, $gpsLng, $schoolLat, $schoolLng);
        $jarak = round($jarak, 1);

        if ($jarak <= $radius) {
            $lokasiValid = true;
        } else {
            $lokasiValid = false;
            $flags[] = 'luar_jangkauan';
        }

        if ($accuracy !== null && $accuracy > 50) {
            $flags[] = 'akurasi_rendah';
        }

        $exifCocok = null;
        if ($exifLat !== null && $exifLng !== null) {
            $exifJarak = self::hitungJarak($gpsLat, $gpsLng, $exifLat, $exifLng);
            $exifCocok = $exifJarak <= 100;

            if (! $exifCocok) {
                $flags[] = 'exif_conflict';
            }
        } else {
            $flags[] = 'exif_hilang';
        }

        return [
            'lokasi_valid' => $lokasiValid,
            'flags' => $flags,
            'jarak' => $jarak,
            'exif_cocok' => $exifCocok,
        ];
    }

    public static function labelKeamanan(array $flags): array
    {
        if (in_array('exif_conflict', $flags)) {
            return ['label' => 'EXIF Conflict', 'warna' => 'bg-red-500', 'text' => 'Lokasi foto berbeda dengan GPS'];
        }
        if (in_array('no_gps', $flags)) {
            return ['label' => 'Tidak Ada GPS', 'warna' => 'bg-gray-400', 'text' => 'Presensi tanpa data lokasi'];
        }
        if (in_array('luar_jangkauan', $flags)) {
            return ['label' => 'Luar Lokasi', 'warna' => 'bg-red-500', 'text' => 'Presensi dari luar area sekolah'];
        }
        if (in_array('exif_hilang', $flags)) {
            return ['label' => 'EXIF Hilang', 'warna' => 'bg-orange-400', 'text' => 'Foto tidak memiliki data GPS'];
        }
        if (in_array('akurasi_rendah', $flags)) {
            return ['label' => 'Akurasi Rendah', 'warna' => 'bg-orange-400', 'text' => 'Sinyal GPS lemah (>50m)'];
        }

        return ['label' => 'Aman', 'warna' => 'bg-green-500', 'text' => 'Lokasi sesuai & EXIF cocok'];
    }
}
