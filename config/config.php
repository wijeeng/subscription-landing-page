<?php

return [
    'api' => [
        'prod' => [
            'check-subscription' => 'http://3.150.64.119',
            'send-pin' => 'http://3.150.64.119',
            'confirm-pin' => 'http://3.150.64.119',
        ],
        'mock' => [
            'check-subscription' => 'http://localhost/mdg/mock/check-subscription/',
            'send-pin' => 'http://localhost/mdg/mock/send-pin/',
            'confirm-pin' => 'http://localhost/mdg/mock/confirm-pin/',
        ],
    ],
];
