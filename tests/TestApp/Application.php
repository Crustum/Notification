<?php
declare(strict_types=1);

namespace TestApp;

use Authentication\AuthenticationService;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;

/**
 * Test Application
 */
class Application extends BaseApplication
{
    /**
     * Load all the application configuration and bootstrap logic
     *
     * @return void
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin('Crustum/Notification', ['routes' => true]);
    }

    /**
     * Setup the middleware queue
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $authenticationService = new AuthenticationService();
        $authenticationService->loadAuthenticator('Authentication.Session');
        $middlewareQueue
            ->add(new RoutingMiddleware($this))
            ->add(new AuthenticationMiddleware($authenticationService))
            ->add(new BodyParserMiddleware());

        return $middlewareQueue;
    }

    /**
     * Define the routes for the application
     *
     * @param \Cake\Routing\RouteBuilder $routes Routes builder
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->setExtensions(['json']);

        parent::routes($routes);

        $routes->scope('/', function (RouteBuilder $builder): void {
                $builder->fallbacks();
        });
    }
}
