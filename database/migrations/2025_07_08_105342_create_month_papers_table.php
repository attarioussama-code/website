<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('month_papers', function (Blueprint $table) {
            $table->id();
            $table->string('file1'); // اسم أو مسار الملف الأول
            $table->string('file2'); // اسم أو مسار الملف الثاني
            $table->string('file3'); // اسم أو مسار الملف الثالث
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('month_papers');
    }
};
