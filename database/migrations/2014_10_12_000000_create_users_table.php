<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('national_number')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->date('birth_date')->nullable();
            $table->enum('role', ['owner', 'admin','driver'])->default('owner');
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp', 'otp_expires_at']);
        });
    }
};
