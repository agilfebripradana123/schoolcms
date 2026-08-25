<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id');
            $table->enum('type', ['stock_in', 'stock_out', 'adjustment']);
            $table->unsignedInteger('quantity');
            $table->enum('adjustment_type', ['increase', 'decrease'])->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('inventory_id')
                ->references('id')
                ->on('inventories')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('inventory_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
