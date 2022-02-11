<?php

declare(strict_types=1);

use DI\Container;
use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use Slim\Factory\AppFactory;
use Slim\Views\{Twig,TwigMiddleware};
use Twig\Extra\Intl\IntlExtension;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$container->set('view', function(\Psr\Container\ContainerInterface $container) {
    $twig = Twig::create(__DIR__ . '/../resources/templates');
    $twig->addExtension(new IntlExtension());
    return $twig;
});
$container->set(
    'database',
    fn($c) => new PDO(sprintf('sqlite:%s', __DIR__ . '/../data/database/weather_station.sqlite'))
);

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(TwigMiddleware::createFromContainer($app));

$app->map(['GET'], '/', function (Request $request, Response $response, array $args) {
    /** @var PDO $dbh */
    $dbh = $this->get('database');

    if (isset($request->getQueryParams()['date'])) {
        $statement = $dbh->prepare('SELECT temperature, humidity, date(timestamp) as date, time(timestamp) as time 
            FROM weather_data 
            WHERE date(timestamp ) = :date
            ORDER BY timestamp DESC;'
        );
        $statement->bindParam(':date', $request->getQueryParams()['date'], PDO::PARAM_STR);
        $statement->execute();
        $weatherData = $statement->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $weatherData = $dbh->query(
            'SELECT temperature, humidity, date(timestamp) as date, time(timestamp) as time 
            FROM weather_data 
            ORDER BY timestamp DESC;',
            PDO::FETCH_ASSOC
        );
    }

    return $this->get('view')->render(
        $response,
        'index.html.twig',
        ['items' => $weatherData]
    );
});

$app->run();