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
            $table->string('latitude');
            $table->string('longitude');
            $table->float('speed')->nullable();   
            $table->float('heading')->nullable(); 

            // 2. بيانات المحرك والارتفاع (OBD & GPS)
            $table->integer('rpm')->nullable();       // دوران المحرك
            $table->float('altitude')->nullable();    // الارتفاع (لمعرفة السقوط)

            // 3. قراءات الحساسات (IMU Sensors) - حسب الجدول
            $table->float('ax')->nullable();  // تسارع أمامي/خلفي (للفرملة)
            $table->float('ay')->nullable();  // تسارع جانبي (للانعطاف)
            $table->float('az')->nullable();  // تسارع رأسي (للمطبات)
            $table->float('yaw')->nullable(); // زاوية الانحراف (للانعطاف العدواني)

            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('trip_locations');
    }
};
