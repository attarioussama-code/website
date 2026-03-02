<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->foreignId('sent_to')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // تأكد أن العمود موجود قبل محاولة الحذف لتجنب الخطأ
        if (Schema::hasColumn('files', 'sent_to')) {
            Schema::table('files', function (Blueprint $table) {
                $table->dropForeign(['sent_to']); // حذف المفتاح الأجنبي
                $table->dropColumn('sent_to');    // حذف العمود نفسه
            });
        }
    }
};
