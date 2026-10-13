<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balcony_fred_observations', function (Blueprint $table): void {
            $table->id();
            $table->string('series_id', 32);
            $table->date('observed_at');
            $table->decimal('value', 14, 4)->nullable();
            $table->timestamps();

            $table->unique(['series_id', 'observed_at'], 'balcony_fred_observations_series_observed_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balcony_fred_observations');
    }
};
