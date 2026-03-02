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
                'major_crash',      // حادث جسيم (SOS)
                'hard_braking',     // فرملة حادة (Scoring)
                'aggressive_turn',  // انعطاف عدواني (Scoring)
                'road_bump'         // مطب 
            ]);

            // مستوى الخطورة (بنحدده احنا بناءً على النوع)
            $table->string('severity')->default('low'); // low, medium, critical

            // القيم الفيزيائية وقت الحدث (للتوثيق Verification)
            $table->float('g_force_x')->nullable(); // Ax
            $table->float('g_force_y')->nullable(); // Ay
            $table->float('g_force_z')->nullable(); // Az
            $table->float('yaw')->nullable();       // Yaw Change
            $table->float('speed_before')->nullable();
            $table->integer('rpm_before')->nullable();

            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('crashes');
    }
};