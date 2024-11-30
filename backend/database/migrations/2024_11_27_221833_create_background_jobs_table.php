<?php

use App\Enums\JobPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('background_jobs', function (Blueprint $table) {
            $table->id();
            $table->integer('pid')->nullable();
            $table->string('classname');
            $table->string('method');
            $table->json('params')->nullable();
            $table->enum('status', ['pending', 'running'])->default('pending');
            $table->integer('retries')->default(0);
            $table->integer('max_retries')->default(1);
            $table->integer('retry_delay')->default(60);
            $table->enum(
                'priority',
                collect(JobPriority::cases())->map(fn($x) => $x->value)->toArray()
            )->default('medium');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('available_at');
            $table->integer('timeout');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_jobs');
    }
};
