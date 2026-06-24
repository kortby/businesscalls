<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Call Quality Score (CQS) Weights
    |--------------------------------------------------------------------------
    |
    | Define the w1, w2, and w3 normalized weights used to calculate the Call
    | Quality Score (CQS) for completed calls at each plan level.
    |
    | Formula: CQS = w1 * (1 - (Delta / 1500)) + w2 * Theta + w3 * Epsilon
    |
    */
    'cqs_weights' => [
        'trial' => [
            'w1' => 0.3,
            'w2' => 0.3,
            'w3' => 0.4,
        ],
        'basic' => [
            'w1' => 0.4,
            'w2' => 0.3,
            'w3' => 0.3,
        ],
        'premium' => [
            'w1' => 0.3,
            'w2' => 0.4,
            'w3' => 0.3,
        ],
        'enterprise' => [
            'w1' => 0.2,
            'w2' => 0.4,
            'w3' => 0.4,
        ],
    ],
];
