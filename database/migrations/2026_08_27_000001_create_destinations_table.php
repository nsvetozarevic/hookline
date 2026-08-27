<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('endpoints')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('timeout_seconds');
            $table->unsignedSmallInteger('max_attempts');
            $table->jsonb('headers')->nullable();
            $table->timestamps();
        });
    }
};
