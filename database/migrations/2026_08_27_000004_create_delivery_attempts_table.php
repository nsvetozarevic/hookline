<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->jsonb('request_headers');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body_snippet')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->text('error')->nullable();
            $table->timestamp('created_at');
        });
    }
};
