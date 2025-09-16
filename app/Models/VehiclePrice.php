<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePrice extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->table = config('database.default') === 'sqlsrv' ? 'cobrabot.vehicle_prices' : 'vehicle_prices';
    }

    protected $fillable = [
        'yielding_date',
        'car_group',
        'type',
        'start_date',
        'end_date',
        'yield',
        'yield_code',
        'price',
        'pool',
    ];

    protected $casts = [
        'yielding_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:4',
    ];
}
