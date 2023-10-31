<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->timestamp('order_date');
            $table->timestamps();
        });

        // to enable full-text search on the customer_name column:
        // add a full-text index to the customer_name column
        DB::statement('ALTER TABLE orders ADD FULLTEXT customer_name_fulltext (customer_name)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('customer_name_fulltext');
        });

        Schema::dropIfExists('orders');
    }
};