<?php

declare(strict_types=1);

namespace WeatherStation\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Paginator\Paginator;
use Laminas\Paginator\Adapter\Iterator as PaginatorIterator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use WeatherStation\Service\WeatherService;

class WeatherDataResultsController
{
    private Twig $view;
    private WeatherService $weatherService;
    private ?LoggerInterface $logger;

    public function __construct(Twig $view, WeatherService $weatherService, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->view = $view;
        $this->weatherService = $weatherService;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $startDate = $request->getAttribute('startDate');
        $endDate = $request->getAttribute('endDate');
        $pageNumber = $request->getAttribute('pageNumber', 1);

        if ($this->logger !== null) {
            $this->logger
                ->debug(
                    'Requested data',
                    [
                        'start date' => $startDate ?? 'not supplied',
                        'end date' => $endDate ?? 'not supplied'
                    ]
                );
        }

        $weatherData = $this->getWeatherData($startDate, $endDate);

        if ($request->hasHeader("content-type") && $request->getHeaderLine("content-type") === 'text/csv') {
            $csvFileFromWeatherData = createCsvFileFromWeatherData($weatherData);
            return new JsonResponse(
                [
                    'data' => $csvFileFromWeatherData->getContents(),
                    'size' => $csvFileFromWeatherData->getSize(),
                ]
            );
        }

        $paginator = new Paginator(new PaginatorIterator($weatherData));
        return $this
            ->view
            ->render(
                $response,
                'index.html.twig',
                [
                    'current' => $pageNumber,
                    'endDate' => $endDate,
                    'items' => $paginator->getItemsByPage($pageNumber),
                    'nearbyPagesLimit' => 4,
                    'startDate' => $startDate,
                    'total' => 10,
                    'totalItems' => $weatherData->count(),
                    'url' => 'page',
                    'urlExtras' => [
                        $startDate,
                        $endDate,
                    ]
                ]
            );
    }

    /**
     * @return \Laminas\Db\ResultSet\ResultSet|\Laminas\Db\ResultSet\ResultSetInterface
     */
    public function getWeatherData(?string $startDate = null, ?string $endDate = null)
    {
        return $this
            ->weatherService
            ->getWeatherData($startDate, $endDate);
    }
}