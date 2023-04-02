<?php

declare(strict_types=1);

use DI\ContainerBuilder;

return function ($container) {
    $container->set('db', function($container) {
        return ;
    });

};
