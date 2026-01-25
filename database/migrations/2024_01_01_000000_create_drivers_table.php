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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            
            // 1. حساب السواق الشخصي (عشان يعمل Login)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // 2. المالك اللي السواق شغال عنده (الجديد)
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('no action');

            $table->string('name');
            $table->string('national_number')->unique();
            $table->string('license_number')->unique();
            $table->string('email')->unique();
            $table->string('password'); 
            $table->string('phone')->nullable();
            
            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};