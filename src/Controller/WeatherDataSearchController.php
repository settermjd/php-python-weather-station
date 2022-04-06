<?php

namespace WeatherStation\Controller;

use DateTimeImmutable;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Date;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WeatherDataSearchController
{
    public const DATE_FORMAT = 'Y-m-d';

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $searchData = $request->getParsedBody();
        $validator = new Date([
            'format' => self::DATE_FORMAT,
            'strict' => true,
        ]);

        //var_dump($searchData); exit;

        if (
            $validator->isValid($searchData['startDate'] ?? null) &&
            $validator->isValid($searchData['endDate'] ?? null)
        ) {
            return new RedirectResponse(
                sprintf(
                    '/page1/%s/%s/',
                    $this->getFormattedDateString($searchData['startDate']),
                    $this->getFormattedDateString($searchData['endDate'])
                )
            );
        }

        if ($validator->isValid($searchData['startDate'] ?? null)) {
            return new RedirectResponse(
                sprintf(
                    '/page1/%s/',
                    $this->getFormattedDateString($searchData['startDate'])
                )
            );
        }

        return new RedirectResponse('/');
    }

    /**
     * @param $startDate
     * @return string
     * @throws \Exception
     */
    public function getFormattedDateString($startDate): string
    {
        return (new DateTimeImmutable($startDate))
            ->format(self::DATE_FORMAT);
    }
}