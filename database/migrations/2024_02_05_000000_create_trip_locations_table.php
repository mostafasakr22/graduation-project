<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');

            // 1. بيانات الموقع والسرعة (Basic GPS)
            $table->string('latitude'); // دوائر العرض
            $table->string('longitude'); // خطوط الطول
            $table->float('speed')->nullable();   // السرعه 
            $table->float('heading')->nullable(); // الاتجاه 
            $table->float('altitude')->nullable();    // الارتفاع (لمعرفة السقوط)
            $table->integer('sats')->nullable(); // عدد الأقمار الصناعية

            // 2. بيانات المحرك  (OBD)
            $table->integer('rpm')->nullable();       // دوران المحرك
            $table->float('coolant_temp')->nullable(); // حرارة المحرك
            $table->float('fuel_level')->nullable();   // مستوى الوقود
            $table->string('dtc_codes')->nullable();   // أكواد الأعطال


            // 3. قراءات الحساسات (IMU Sensors) - حسب الجدول
            $table->float('ax')->nullable();  // تسارع أمامي/خلفي (للفرملة)
            $table->float('ay')->nullable();  // تسارع جانبي (للانعطاف)
            $table->float('az')->nullable();  // تسارع رأسي (للمطبات)
            $table->float('yaw')->nullable(); // زاوية الانحراف (للانعطاف العدواني)
            $table->float('pitch')->nullable(); // ميلان أمامي/خلفي
            $table->float('roll')->nullable();  // ميلان يمين/يسار

            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('trip_locations');
    }
};
