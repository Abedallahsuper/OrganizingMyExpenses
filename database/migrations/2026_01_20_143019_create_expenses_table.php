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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);//let expense amount be 100$ for category food
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();//let category_id be 1 for category food
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();//let user_id be 1 for user admin
            $table->date('expense_date');//let expense_date be 2026-01-20
            $table->year('year')->default(date('Y'));//let year be 2026
            $table->string('month')->default(date('m'));//let month be 01
            $table->timestamps();
            # after end month the system calculate the total amount of expenses for each category and user and store it in the expenses table
            # after end year the system calculate the total amount of expenses for each category and user and store it in the expenses table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
