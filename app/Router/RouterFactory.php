<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(): RouteList
    {
        $router = new RouteList();
        $router->addRoute('sign/in', 'Authentication:login', true);
        $router->addRoute('sign/out', 'Authentication:logout', true);
        $router->addRoute(
            'setting/<action>[/<id>]',
            ['presenter' => 'Settings'],
            true,
        );
        $router->addRoute(
            'koncerty/<action>[/<id>]',
            ['presenter' => 'Concerts'],
            true,
        );
        $router->addRoute(
            'skladby/<action>[/<id>]',
            ['presenter' => 'Songs'],
            true,
        );
        $router->addRoute('<presenter>/<action>[/<id>]', 'Dashboard:default');
        return $router;
    }
}
