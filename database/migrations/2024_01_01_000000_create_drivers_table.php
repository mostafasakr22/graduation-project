<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
             $table->string('name');
            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};