<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // هنا بنضيف العمود الجديد
            // خليناه nullable عشان لو الجدول فيه داتا قديمة ميديناش ايرور
            $table->string('phone')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // هنا بنقوله لو عملنا rollback يمسح العمود ده بس
            $table->dropColumn('phone');
        });
    }
};