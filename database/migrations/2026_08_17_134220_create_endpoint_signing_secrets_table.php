<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('endpoint_signing_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('endpoints')->cascadeOnDelete();
            $table->string('secret', 128);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['endpoint_id', 'expires_at']);
        });

        DB::statement('CREATE UNIQUE INDEX endpoint_signing_secrets_one_current ON endpoint_signing_secrets (endpoint_id) WHERE expires_at IS NULL');
    }
};
