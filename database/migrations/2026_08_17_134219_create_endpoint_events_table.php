<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('endpoint_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('endpoints')->cascadeOnDelete();
            $table->string('deduplication_key');
            $table->json('headers');
            $table->longText('payload');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique(['endpoint_id', 'deduplication_key']);
        });
    }
};
