<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            
            // العلاقات (Relationships)
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade'); 

            // التوقيتات (Timestamps)
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();

            // العناوين (Addresses)
            $table->text('start_address')->nullable();
            $table->text('end_address')->nullable();

            // الإحداثيات (GPS) 
            $table->string('start_lat')->nullable();
            $table->string('start_lng')->nullable();
            $table->string('end_lat')->nullable();
            $table->string('end_lng')->nullable();

            
            $table->enum('status', ['ongoing', 'completed', 'cancelled'])->default('ongoing');
            $table->float('distance_km')->default(0)->nullable();
            $table->float('avg_speed')->default(0)->nullable(); // متوسط السرعة ف الرحله
            $table->float('max_speed')->default(0)->nullable(); // السرعه القصوي اللي وصلها 

            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};