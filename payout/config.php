<?php

// PSC Config File

$config = [

    /*
     * Debug
     * Set Debug, will enable more details on errors
     * 0: No debug messages
     * 1: modal debug messages
     * 2: verbose output (requests, curl & responses)
     */

    'debug_level' => 1,

    /*
     * Key:
     * Set key, your psc key
     */

    'psc_key'     => "psc_xl0EwfLX-96bEkjy-mXYD7SFviyvaqA",

    /*
     * Logging:
     * enable logging of requests and responses to file, default: true
     * might be disbaled in production mode
     */

    'logging'     => true,

    /*
     * Sandbox
     * enable the sandbox for testing.
     * Possible Values are:
     * true = Test environment
     * false = Productive Environment
     *
     */

    'sandbox' => true,

];
