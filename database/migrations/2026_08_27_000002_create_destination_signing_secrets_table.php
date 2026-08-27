<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('destination_signing_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->string('secret', 128);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['destination_id', 'expires_at']);
        });

        DB::statement('CREATE UNIQUE INDEX destination_signing_secrets_one_current ON destination_signing_secrets (destination_id) WHERE expires_at IS NULL');
    }
};
