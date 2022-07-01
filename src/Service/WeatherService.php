<?php

declare(strict_types=1);

namespace WeatherStation\Service;

use Exception;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Predicate\Between;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private Adapter $adapter;
    private ?LoggerInterface $logger;

    public function __construct(Adapter $adapter, ?LoggerInterface $logger = null)
    {
        $this->adapter = $adapter;
        $this->logger  = $logger;
    }

    /**
     * @throws Exception
     */
    public function getWeatherData(?string $startDate, ?string $endDate = null): StatementInterface|ResultSet
    {
        $sql    = new Sql($this->adapter, 'weather_data');
        $select = $sql
            ->select()
            ->columns(['humidity', 'temperature', 'timestamp']);

        if ($startDate !== null && $endDate !== null) {
            $select->where(
                new Between(
                    "timestamp",
                    $startDate,
                    $endDate
                )
            );
        } elseif ($startDate !== null) {
            $select->where(
                new Expression(
                    "timestamp = ?",
                    $startDate
                )
            );
        }

        $sqlString = $sql->buildSqlString($select);

        $this->logger?->debug(
            $sqlString,
            [
                'start date' => $startDate,
                'end date'   => $endDate,
            ]
        );

        return $this
            ->adapter
            ->query(
                $sqlString,
                $this->adapter::QUERY_MODE_EXECUTE
            );
    }
}
