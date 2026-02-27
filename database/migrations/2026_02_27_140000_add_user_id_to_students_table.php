<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->unique()->after('class_id');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
