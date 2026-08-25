<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedInteger('room_id')->nullable();
            $table->string('reported_by', 100)->nullable();
            $table->enum('maintenance_type', ['corrective', 'preventive', 'emergency', 'inspection']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('scheduled_date')->nullable();
            $table->date('started_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('asset_id')
                ->references('id')
                ->on('assets')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->index('asset_id');
            $table->index('room_id');
            $table->index('status');
            $table->index('priority');
            $table->index('maintenance_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance');
    }
};
