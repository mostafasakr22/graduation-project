<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::create('crashes', function (Blueprint $table) {
            $table->id();
            // الربط
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('no action');
            
            // التوقيت والمكان
            $table->dateTime('crashed_at');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('location')->nullable(); // العنوان النصي

            // تصنيف الحدث (زي ما بتوع هندسه عايزين)
            $table->enum('type', [
                'major_crash',      // حادث (اصطدام/انقلاب)
                'hard_braking',     // فرملة حادة
                'aggressive_turn',  // انعطاف عدواني
                'road_bump',        // مطب عنيف
                'early_warning',    // بداية عطل ميكانيكي (حرارة/اهتزاز)
                'fuel_leak'         // تسريب وقود
            ]);

            // مستوى الخطورة (بنحدده احنا بناءً على النوع)
            $table->string('severity')->default('low'); // low, medium, critical

            // القيم الفيزيائية وقت الحدث (للتوثيق Verification)
            // 1. حساسات الحركة (IMU)
            $table->float('ax')->nullable();    
            $table->float('ay')->nullable();    
            $table->float('az')->nullable();    
            $table->float('yaw')->nullable();   // الدوران
            $table->float('pitch')->nullable(); // الصعود/الهبوط (الجديد)
            $table->float('roll')->nullable();  // الميلان الجانبي (الجديد)

            // 2. بيانات المحرك والسيارة (OBD-II & GPS)
            $table->float('speed_before')->nullable();
            $table->integer('rpm_before')->nullable();
            $table->float('coolant_temp')->nullable(); // حرارة المحرك (الجديد)
            $table->float('fuel_level')->nullable();   // مستوى الوقود (الجديد)
            $table->string('dtc_codes')->nullable();   // أكواد الأعطال (الجديد)
            $table->integer('sats')->nullable();       // جودة إشارة الأقمار (الجديد)



            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('crashes');
    }
};