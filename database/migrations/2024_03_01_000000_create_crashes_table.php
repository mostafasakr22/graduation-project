<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crashes', function (Blueprint $table) {
            $table->id();
            
            // العلاقات
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('no action');
           
            // التوقيت
            $table->dateTime('crashed_at');  

            // المكان (الإحداثيات للخريطة + العنوان للنص)
            $table->string('latitude');  
            $table->string('longitude'); 
            $table->string('location')->nullable();

            // تفاصيل الحادث
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->float('speed_before')->nullable();
            $table->float('acceleration_impact')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crashes');
    }
};