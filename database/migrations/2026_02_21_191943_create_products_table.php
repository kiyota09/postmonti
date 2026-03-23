<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique();  // Auto-generated: PRD-001
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->string('status')->default('Active'); // Active | Draft | Inactive
            $table->string('color_tag')->nullable();
            $table->string('color_hex')->nullable();
            $table->string('color_name')->nullable();
            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->unsignedInteger('batch_size')->nullable();
            $table->string('lead_time')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->unsignedInteger('stock_on_hand')->default(0);
            $table->unsignedInteger('moq')->nullable();
            $table->string('certification')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
