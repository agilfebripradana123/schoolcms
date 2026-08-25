<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('category', [
                'electronics',
                'furniture',
                'lab_equipment',
                'sports',
                'teaching_aids',
                'office',
                'other',
            ]);
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('condition', ['good', 'fair', 'poor', 'damaged']);
            $table->string('location', 150)->nullable();
            $table->unsignedInteger('room_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->index('room_id');
            $table->index('category');
            $table->index('status');
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
