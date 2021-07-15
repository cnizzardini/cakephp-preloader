<?php
/*
|--------------------------------------------------------------------------
| CakePHP Preloader Config | https://github.com/cnizzardini/cakephp-preloader
|--------------------------------------------------------------------------
*/

return [
    'PreloaderConfig' => [
        /*
        |--------------------------------------------------------------------------
        | name <string>
        |--------------------------------------------------------------------------
        |
        | The preload file path. (default: ROOT . DS . 'preload.php')
        |
        */
        'name' => ROOT . DS . 'preload.php',

        /*
        |--------------------------------------------------------------------------
        | app <boolean>
        |--------------------------------------------------------------------------
        |
        | Should your APP files be included in the preloader (default: false)?
        |
        */
        'app' => false,

        /*
        |--------------------------------------------------------------------------
        | packages <string[]>
        |--------------------------------------------------------------------------
        |
        | An array of composer packages to include in your preloader.
        |
        | @example ['cakephp/authentication','cakephp/chronos']
        */
        'packages' => [],

        /*
        |--------------------------------------------------------------------------
        | plugins <string[]|boolean>
        |--------------------------------------------------------------------------
        |
        | An array of your applications plugins to include in your preloader. To include
        | all your plugins set to `true`.
        |
        | @example ['MyPlugin','MyOtherPlugin']
        */
        'plugins' => [],
    ]
];
