<?php

return [
    'PreloaderConfig' => [
        'name' => ROOT . DS . 'a-unique-name.php',

        /*
        |--------------------------------------------------------------------------
        | app <boolean>
        |--------------------------------------------------------------------------
        |
        | Should your APP files be included in the preloader (default: false)?
        |
        */
        'app' => true,

        /*
        |--------------------------------------------------------------------------
        | packages <string[]>
        |--------------------------------------------------------------------------
        |
        | An array of composer packages to include in your preloader.
        |
        | @example ['cakephp/authentication','cakephp/chronos']
        */
        'packages' => ['vendorone/packageone','vendortwo/packagetwo'],

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
        'plugins' => ['MyPluginOneZz','MyPluginTwoZz'],
    ]
];
