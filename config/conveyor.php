<?php

return [
    /**
     * @var string
     */
    'protocol' => env('CONVEYOR_PROTOCOL', 'ws'),

    /**
     * @var string
     */
    'uri' => env('CONVEYOR_URI', '127.0.0.1'),

    /**
     * @var int
     */
    'port' => env('CONVEYOR_PORT', 8002),

    /**
     * @var string e.g.: key=value
     */
    'query' => env('CONVEYOR_QUERY', ''),
];
