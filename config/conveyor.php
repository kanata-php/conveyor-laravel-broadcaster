<?php

echo [
    /**
     * @var string
     */
    'protocol' => 'ws',
    
    /**
     * @var string
     */
    'uri' => '127.0.0.1',
    
    /**
     * @var int
     */
    'port' => 8002,
    
    /**
     * @var string
     */
    'query' => '',
    
    /**
     * @var ?string
     */
    'channel' =>  null,
    
    /**
     * @var ?string
     */
    'listen' => null,
    
    /**
     * @var ?callable
     */
    'onOpenCallback' => null,
    
    /**
     * @var ?callable
     */
    'onReadyCallback' => null,
    
    /**
     * Callback for incoming messages.
     * Passed parameters:
     *   - \WebSocket\Client $client
     *   - string $message
     *
     * @var ?callable
     */
    'onMessageCallback' => null,
    
    /**
     * Callback for disconnection.
     * Passed parameters:
     *   - \WebSocket\Client $client
     *   - int $reconnectionAttemptsCount
     *
     * @var ?callable
     */
    'onDisconnectCallback' => null,
    
    /**
     * When positive, considered in seconds
     *
     * @var int
     */
    'timeout' => -1,
    
    /**
     * @var bool
     */
    'reconnect' => false,
    
    /**
     * Number of attempts if disconnects
     * For this to keeps trying forever, set it to -1. 
     *
     * @var int
     */
    'reconnectionAttempts' => 0,
    
    /**
     * Interval to reconnect in seconds 
     * 
     * @var int 
     */
    'reconnectionInterval' => 2,
];
