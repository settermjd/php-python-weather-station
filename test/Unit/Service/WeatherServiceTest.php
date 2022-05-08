<?php

use WeatherStation\Service\WeatherService;

test('Weather service isSet call should always return true', function () {
    /** @var WeatherService|\Mockery\MockInterface|\Mockery\LegacyMockInterface $weatherService */
    $weatherService = mock(WeatherService::class)
        ->shouldReceive('isSet')
        ->once()
        ->andReturn(true)
        ->getMock();
    expect($weatherService->isSet())->toBeTrue();

    /*$weatherService = mock(WeatherService::class)->expect(
        isSet: fn ($name) => true,
    );
    expect($weatherService->isSet())->toBeTrue();*/

});
