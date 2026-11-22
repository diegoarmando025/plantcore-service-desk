<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_portfolio_demo')->default(false)->index();
        });
        Schema::table('tickets', function (Blueprint $table): void {
            $table->boolean('is_portfolio_demo')->default(false)->index();
        });
    }
    public function down(): void {
        Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn('is_portfolio_demo'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_portfolio_demo'));
    }
};
