<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('type')->default('income');
                $table->bigInteger('amount')->default(0);
                $table->string('currency_code')->nullable();
                $table->date('date')->nullable();
                $table->text('notes')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
            return;
        }

        // Table exists — add any missing columns used by mobile flows.
        $cols = [
            'company_id' => fn($table) => $table->unsignedBigInteger('company_id')->nullable(),
            'user_id' => fn($table) => $table->unsignedBigInteger('user_id')->nullable(),
            'currency_code' => fn($table) => $table->string('currency_code')->nullable(),
            'date' => fn($table) => $table->date('date')->nullable(),
            'notes' => fn($table) => $table->text('notes')->nullable(),
            'description' => fn($table) => $table->text('description')->nullable(),
            'category_id' => fn($table) => $table->unsignedBigInteger('category_id')->nullable(),
            'paid_at' => fn($table) => $table->timestamp('paid_at')->nullable(),
        ];

        foreach ($cols as $col => $adder) {
            if (! Schema::hasColumn('transactions', $col)) {
                Schema::table('transactions', function (Blueprint $table) use ($adder) {
                    $adder($table);
                });
            }
        }
    }

    public function down()
    {
        // safe no-op for down to avoid accidentally dropping production columns
    }
};
