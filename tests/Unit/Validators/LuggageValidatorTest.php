<?php

use App\Enums\LuggageStatusEnum;
use App\Enums\TripStatusEnum;
use App\Models\Luggage;
use App\Models\Trip;
use App\Enums\TripTypeEnum;
use App\Validators\LuggageValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('refuse la réservation si le trajet est fermé', function () {
    $trip = new Trip([
        'capacity'    => 10,
        'status'      => TripStatusEnum::CANCELLED,
        'type_trip'   => TripTypeEnum::STANDARD,
        'date'        => now()->addDays(1),
    ]);

    $luggage = new Luggage([
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $validator = app(LuggageValidator::class);

    $validator->validateReservation($luggage, $trip, 1.0);
})->throws(ValidationException::class, 'Ce trajet est clôturé.');
