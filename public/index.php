<?php

declare(strict_types=1);

use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Diactoros\Response\TextResponse;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use Slim\Factory\AppFactory;
use Slim\Views\{Twig,TwigMiddleware};
use Twig\Extra\Intl\IntlExtension;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->set('view', function() {
    $twig = Twig::create(__DIR__ . '/../resources/templates');
    $twig->addExtension(new IntlExtension());
    return $twig;
});

$container->set('dbAdapter', function() {
    return new Adapter([
        'driver' => 'Pdo_Sqlite',
        'database' => __DIR__ . '/../data/database/weather_station_test_data.sqlite'
    ]);
});

$container->set('weatherService', function(ContainerInterface $container) {
    return new WeatherStation\Service\WeatherService(
        $container->get('dbAdapter')
    );
});

$container->set('logger', function(ContainerInterface $container) {
    $logger = new Monolog\Logger('logger');
    $filename = __DIR__ . '/../data/log/error.log';
    $stream = new Monolog\Handler\StreamHandler(
        $filename,
        Monolog\Logger::DEBUG
    );
    $logger->pushHandler($stream);

    return $logger;
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(TwigMiddleware::createFromContainer($app));

$app->map(['GET'], '/[{forDate}]', function (Request $request, Response $response, array $args) {
    /** @var WeatherStation\Service\WeatherService $weatherService */
    $weatherService = $this->get('weatherService');
    $forDate = $request->getAttribute('forDate');

    /** @var Logger $logger */
    $logger = $this->get('logger');
    $logger->debug('Requested data', ['for date' => $forDate]);

    return $this->get('view')->render(
        $response,
        'index.html.twig',
        ['items' => $weatherService->getWeatherData($forDate)]
    );
});

$app->map(['GET'], '/download[/{forDate}]', function (Request $request, Response $response, array $args) {
    /** @var WeatherStation\Service\WeatherService $weatherService */
    $weatherService = $this->get('weatherService');
    $forDate = $request->getAttribute('forDate');
    $weatherData = $weatherService->getWeatherData($forDate);
    $filename = 'daily-summary';

    $fp = fopen('php://memory', 'rw');
    foreach ($weatherData->toArray() as $weatherDatum) {
        fputcsv($fp, $weatherDatum);
    }
    $stream = new \Laminas\Diactoros\Stream($fp);

    return new TextResponse(
        $stream,
        200,
        [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
            'Content-Length' => $stream->getSize(),
            'Pragma' => 'no-cache',
            'Expires' => 0,
        ]
    );
});

$app->run();