<?php

declare(strict_types=1);

namespace WeatherStation\Service;

use Laminas\Db\{
    Adapter\Adapter,
    ResultSet\ResultSetInterface,
    Sql\Predicate\Between,
    Sql\Predicate\Expression,
    Sql\Sql
};

class WeatherService
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getWeatherData(?string $startDate, ?string $endDate = null): ResultSetInterface
    {
        $sql = new Sql($this->adapter, 'weather_data');
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

        return $this
            ->adapter
            ->query(
                $sqlString,
                $this->adapter::QUERY_MODE_EXECUTE
            );
    }
}