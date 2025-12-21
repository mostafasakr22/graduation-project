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

            $table->string('latitude');
            $table->string('longitude');
            $table->float('speed')->nullable();
            $table->float('heading')->nullable();

            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('trip_locations');
    }
};
