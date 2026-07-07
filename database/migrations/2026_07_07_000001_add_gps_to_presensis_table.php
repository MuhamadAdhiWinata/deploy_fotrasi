<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->decimal('lat_check_in', 10, 7)->nullable()->after('foto_check_in');
            $table->decimal('lng_check_in', 10, 7)->nullable()->after('lat_check_in');
            $table->decimal('gps_accuracy_in', 8, 2)->nullable()->after('lng_check_in');

            $table->decimal('lat_check_out', 10, 7)->nullable()->after('foto_check_out');
            $table->decimal('lng_check_out', 10, 7)->nullable()->after('lat_check_out');
            $table->decimal('gps_accuracy_out', 8, 2)->nullable()->after('lng_check_out');

            $table->decimal('exif_lat_in', 10, 7)->nullable()->after('gps_accuracy_in');
            $table->decimal('exif_lng_in', 10, 7)->nullable()->after('exif_lat_in');
            $table->decimal('exif_lat_out', 10, 7)->nullable()->after('gps_accuracy_out');
            $table->decimal('exif_lng_out', 10, 7)->nullable()->after('exif_lat_out');

            $table->string('ip_address', 45)->nullable()->after('keterangan');
            $table->decimal('ip_lat', 10, 7)->nullable()->after('ip_address');
            $table->decimal('ip_lng', 10, 7)->nullable()->after('ip_lat');

            $table->boolean('lokasi_valid')->nullable()->after('ip_lng');
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropColumn([
                'lat_check_in', 'lng_check_in', 'gps_accuracy_in',
                'lat_check_out', 'lng_check_out', 'gps_accuracy_out',
                'exif_lat_in', 'exif_lng_in', 'exif_lat_out', 'exif_lng_out',
                'ip_address', 'ip_lat', 'ip_lng',
                'lokasi_valid',
            ]);
        });
    }
};
