<?php
declare(strict_types=1);

/**
 * Notification Plugin Routes
 *
 * Routes for Notification plugin.
 */

use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass('DashedRoute');

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/posts/publish', ['controller' => 'Posts', 'action' => 'publish']);
        $builder->connect('/posts/notify', ['controller' => 'Posts', 'action' => 'notify']);

        $builder->fallbacks('DashedRoute');
    });
};
