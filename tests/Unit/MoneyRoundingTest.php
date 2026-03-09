<?php

use App\Support\Money;

it('rounds to the nearest 0.05', function (float $input, float $expected) {
    expect(Money::roundToNearest05($input))->toBe($expected);
})->with([
    [0.00, 0.00],
    [0.01, 0.00],
    [0.02, 0.00],
    [0.03, 0.05],
    [0.04, 0.05],
    [0.05, 0.05],
    [0.06, 0.05],
    [0.07, 0.05],
    [0.08, 0.10],
    [1.22, 1.20],
    [1.23, 1.25],
    [1.24, 1.25],
    [1.25, 1.25],
    [-1.22, -1.20],
    [-1.23, -1.25],
]);

