<?php

declare(strict_types=1);

use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use Slim\Factory\AppFactory;
use Slim\Views\{Twig,TwigMiddleware};
use Twig\Extra\Intl\IntlExtension;
use WeatherStation\Service\WeatherService;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$container->set('view', function() {
    $twig = Twig::create(__DIR__ . '/../resources/templates');
    $twig->addExtension(new IntlExtension());
    return $twig;
});
$container->set('weatherData', function() {
    $dbAdapter = new Adapter([
        'driver' => 'Pdo_Sqlite',
        'database' => __DIR__ . '/../data/database/weather_station.sqlite'
    ]);
    $sql = new Sql($dbAdapter, 'weather_data');
    $select = $sql
        ->select()
        ->columns(['temperature', 'humidity', 'timestamp']);

    return $dbAdapter->query(
        $sql->buildSqlString($select),
        $dbAdapter::QUERY_MODE_EXECUTE
    );
});

$container->set('dbAdapter', function() {
    return new Adapter([
        'driver' => 'Pdo_Sqlite',
        'database' => __DIR__ . '/../data/database/weather_station.sqlite'
    ]);
});

$container->set(WeatherService::class, function(ContainerInterface $container) {
    return new WeatherStation\Service\WeatherService(
        $container->get('dbAdapter')
    );
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(TwigMiddleware::createFromContainer($app));

$app->map(['GET'], '/', function (Request $request, Response $response, array $args) {
    return $this->get('view')->render(
        $response,
        'index.html.twig',
        ['items' => $this->get('weatherData')]
    );
});

$app->run();