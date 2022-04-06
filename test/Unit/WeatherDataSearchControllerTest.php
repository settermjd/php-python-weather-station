<?php

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WeatherStation\Controller\WeatherDataSearchController;

beforeEach(function () {
    $this->request = mock(ServerRequestInterface::class);
    $this->response = mock(ResponseInterface::class);
    $this->controller = new WeatherDataSearchController();
});

test('The weather data search controller redirects to the results controller if start and end dates validate successfully', function (string $startDate, string $endDate) {
    $request = $this->request
        ->shouldReceive('getParsedBody')
        ->once()
        ->andReturn([
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])
        ->getMock();
    $result = $this->controller->__invoke($request, $this->response->makePartial(), []);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    expect($result->getHeaderLine('Location'))->toEqual('/page1/2022-04-21/2022-04-22/');
})->with([
    ['2022-04-21', '2022-04-22'],
]);

test('The weather data search controller redirects to the results controller if only a start date is supplied which validates successfully', function (string $startDate) {
    $request = $this->request
        ->shouldReceive('getParsedBody')
        ->once()
        ->andReturn([
            'startDate' => $startDate
        ])
        ->getMock();
    $result = $this->controller->__invoke($request, $this->response->makePartial(), []);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    expect($result->getHeaderLine('Location'))->toEqual('/page1/2022-04-21/');
})->with([
    ['2022-04-21'],
]);

test('The weather data search controller redirects to the results controller if no dates are supplied', function () {
    $request = $this->request
        ->shouldReceive('getParsedBody')
        ->once()
        ->andReturn([])
        ->getMock();
    $result = $this->controller->__invoke($request, $this->response->makePartial(), []);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    expect($result->getHeaderLine('Location'))->toEqual('/');
});