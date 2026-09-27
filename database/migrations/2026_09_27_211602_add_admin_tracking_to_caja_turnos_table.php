<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('caja_turnos', function (Blueprint $table) {
            $table->foreignId('aperturado_por_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('cerrado_por_id')->nullable()->after('aperturado_por_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja_turnos', function (Blueprint $table) {
            $table->dropForeign(['aperturado_por_id']);
            $table->dropForeign(['cerrado_por_id']);
            $table->dropColumn(['aperturado_por_id', 'cerrado_por_id']);
        });
    }
};
