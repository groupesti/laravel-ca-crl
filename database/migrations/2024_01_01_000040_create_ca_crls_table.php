<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ca_crls', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ca_id')
                ->constrained('certificate_authorities')
                ->cascadeOnDelete();
            $table->string('tenant_id')->nullable()->index();
            $table->bigInteger('crl_number');
            $table->timestamp('this_update');
            $table->timestamp('next_update');
            $table->text('crl_pem');
            $table->binary('crl_der');
            $table->string('signature_algorithm', 50);
            $table->integer('entries_count')->default(0);
            $table->boolean('is_delta')->default(false);
            $table->bigInteger('base_crl_number')->nullable();
            $table->string('storage_path', 2048)->nullable();
            $table->timestamps();

            $table->unique(['ca_id', 'crl_number']);
        });

        // Alter crl_der to LONGBLOB for MySQL (binary column defaults to 255 bytes)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                'ALTER TABLE `ca_crls` MODIFY `crl_der` LONGBLOB NOT NULL',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ca_crls');
    }
};
