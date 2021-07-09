<?php

return [
    'PreloaderConfig' => [
        'name' => ROOT . DS . 'a-unique-name.php',
        'app' => true,
        'packages' => ['vendorone/packageone','vendortwo/packagetwo'],
        'plugins' => ['MyPluginOneZz','MyPluginTwoZz'],
    ]
];
