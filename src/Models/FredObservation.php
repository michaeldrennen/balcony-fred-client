<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient\Models;

use Illuminate\Database\Eloquent\Model;

final class FredObservation extends Model
{
    protected $table = 'balcony_fred_observations';

    protected $guarded = ['id'];

    protected $casts = [
        'observed_at' => 'date',
        'value' => 'decimal:4',
    ];
}
