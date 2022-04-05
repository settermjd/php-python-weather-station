<?php

declare(strict_types=1);

use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use SendGrid\Mail\Mail;
use Slim\Factory\AppFactory;
use Slim\Views\{Twig,TwigMiddleware};
use Twig\Extra\Intl\IntlExtension;
use Twilio\Rest\Client;
use WeatherStation\Service\WeatherService;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(
    __DIR__ . '/../',
    '.env',
    false
);
$dotenv->safeLoad();

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

$container->set(WeatherService::class, function(ContainerInterface $container) {
    return new WeatherStation\Service\WeatherService(
        $container->get('dbAdapter'),
        $container->get(LoggerInterface::class)
    );
});

$container->set(LoggerInterface::class, function(ContainerInterface $container): Logger {
    $logger = new Logger('logger');
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

function createCsvFileFromWeatherData(ResultSetInterface $weatherData): Stream
{
    $fp = fopen('php://memory', 'rw');
    foreach ($weatherData->toArray() as $weatherDatum) {
        fputcsv($fp, $weatherDatum);
    }
    $stream = new Stream($fp);
    $stream->rewind();

    return $stream;
}

function sendDailySummaryEmail(string $startDate, string $endDate, ResultSet $weatherData)
{
    $email = new Mail();
    $email->setFrom($_SERVER['SENDGRID_SEND_FROM'], "Matthew Setter");
    $email->setSubject("Your daily weather station data summary");
    $email->addTo("msetter@twilio.com", "Matthew Setter");
    $email->addContent(
        "text/plain",
        sprintf(
            "Your daily weather station data summary for %s - %s",
            $startDate,
            $endDate
        )
    );
    $email->addContent(
        "text/html",
        sprintf(
            "Your daily weather station data summary for %s - %s",
            $startDate,
            $endDate
        )
    );
    $stream = createCsvFileFromWeatherData($weatherData);
    $file_encoded = base64_encode($stream->getContents());
    $email->addAttachment(
        $file_encoded,
        "text/csv",
        "daily-summary.csv",
        "attachment"
    );
    $sendgrid = new \SendGrid($_SERVER['SENDGRID_API_KEY']);
    try {
        $sendgrid->send($email);
    } catch (Exception $e) {
        echo 'Caught exception: ' . $e->getMessage() . "\n";
    }
}

/**
 * @throws \Twilio\Exceptions\ConfigurationException
 * @throws \Twilio\Exceptions\TwilioException
 */
function sendDailySummarySMS(string $startDate, string $endDate): void
{
    $twilio = new Client($_SERVER["TWILIO_ACCOUNT_SID"], $_SERVER["TWILIO_AUTH_TOKEN"]);
    $twilio
        ->messages
        ->create(
            $_SERVER["SEND_TO"],
            [
                "messagingServiceSid" => $_SERVER["TWILIO_MESSAGING_SERVICE_SID"],
                "body" => sprintf(
                    "Your daily weather station data summary is available at http://localhost/daily-summary/%s/%s",
                    $startDate,
                    $endDate
                ),
            ]
        );
}

$app->get('/daily-summary[/{startDate}[/{endDate}]]', function (Request $request, Response $response, array $args) {
    /** @var WeatherStation\Service\WeatherService $weatherService */
    $weatherService = $this->get(WeatherService::class);
    $startDate = $request->getAttribute('startDate');
    $endDate = $request->getAttribute('endDate');
    $weatherData = $weatherService->getWeatherData($startDate, $endDate);

    if ($weatherData->count() === 0) {
        return new JsonResponse('No weather data available for that date range.');
    }

    sendDailySummarySMS($startDate, $endDate);
    sendDailySummaryEmail($startDate, $endDate, $weatherData);

    return new EmptyResponse();
})->setName('daily-summary');

$app->get('/about', function (Request $request, Response $response, array $args) {
    return $this
        ->get('view')
        ->render($response, 'about.html.twig',);
})->setName('about');

$app->get('/disclaimer', function (Request $request, Response $response, array $args) {
    return $this
        ->get('view')
        ->render($response, 'disclaimer.html.twig',);
})->setName('disclaimer');

$app->get('/impressum', function (Request $request, Response $response, array $args) {
    return $this
        ->get('view')
        ->render($response, 'impressum.html.twig',);
})->setName('impressum');

$app->get('/datenschutzerklaerung', function (Request $request, Response $response, array $args) {
    return $this
        ->get('view')
        ->render($response, 'datenschutzerklaerung.html.twig',);
})->setName('datenschutzerklaerung');

$app->get('/cookie-policy', function (Request $request, Response $response, array $args) {
    return $this
        ->get('view')
        ->render($response, 'cookie.html.twig',);
})->setName('cookie');

$app->get('/privacy-policy', function (Request $request, Response $response, array $args) {
    return $this
        ->get('view')
        ->render($response, 'privacy.html.twig',);
})->setName('privacy');

$app->map(['GET','POST'], '/[{startDate}[/{endDate}]]', function (Request $request, Response $response, array $args) {
    /** @var WeatherStation\Service\WeatherService $weatherService */
    $weatherService = $this->get(WeatherService::class);
    $startDate = $request->getAttribute('startDate');
    $endDate = $request->getAttribute('endDate');

    //$pageNumber = $request->getAttribute('page', 1);

    $this->get(LoggerInterface::class)
        ->debug(
            'Requested data',
            [
                'start date' => $startDate ?? 'not supplied',
                'end date' => $endDate ?? 'not supplied'
            ]
        );

    $weatherData = $weatherService->getWeatherData($startDate, $endDate);

    if ($request->hasHeader("content-type") && $request->getHeaderLine("content-type") === 'text/csv') {
        $csvFileFromWeatherData = createCsvFileFromWeatherData($weatherData);
        return new JsonResponse(
            [
                'data' => $csvFileFromWeatherData->getContents(),
                'size' => $csvFileFromWeatherData->getSize(),
            ]
        );
    }

    /*$paginator = new Paginator(new PaginatorIterator($weatherData));*/
    return $this
        ->get('view')
        ->render(
            $response,
            'index.html.twig',
            [
                'items' => $weatherData,
                /*'items' => $paginator->getItemsByPage($pageNumber),
                'total' => 10,
                'current' => $pageNumber,
                'url' => 'page'*/
            ]
        );
});

$app->run();