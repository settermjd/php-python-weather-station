<?php

declare(strict_types=1);

namespace WeatherStation\Service;

use Laminas\Db\{
    Adapter\Adapter,
    ResultSet\ResultSet,
    ResultSet\ResultSetInterface,
    Sql\Predicate\Between,
    Sql\Predicate\Expression,
    Sql\Sql
};
use Psr\Log\LoggerInterface;

class WeatherService
{
    private Adapter $adapter;
    private ?LoggerInterface $logger;

    public function __construct(Adapter $adapter, ?LoggerInterface $logger = null)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    /**
     * @return ResultSet|ResultSetInterface
     * @throws \Exception
     */
    public function getWeatherData(?string $startDate, ?string $endDate = null)
    {
        $sql = new Sql($this->adapter, 'weather_data');
        $select = $sql
            ->select()
            ->columns(['humidity', 'temperature', 'timestamp']);

        if ($startDate !== null && $endDate !== null) {
            $select->where(
                new Between(
                    new Expression("date(timestamp)"),
                    $startDate,
                    $endDate
                )
            );
        } elseif ($startDate !== null) {
            $select->where(
                new Expression(
                    "date(timestamp) = ?",
                    $startDate
                )
            );
        }

        $sqlString = $sql->buildSqlString($select);

        if ($this->logger !== null) {
            $this->logger->debug(
                $sqlString,
                [
                    'start date' => $startDate,
                    'end date' => $endDate,
                ]
            );
        }

        $results = $this
            ->adapter
            ->query(
                $sqlString,
                $this->adapter::QUERY_MODE_EXECUTE
            );

        $results->buffer();

        return $results;
    }
}