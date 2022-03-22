<?php

declare(strict_types=1);

namespace WeatherStation\Service;

use Laminas\Db\{Adapter\Adapter,
    ResultSet\ResultSetInterface,
    Sql\Predicate\Between,
    Sql\Predicate\Expression,
    Sql\Sql};

class WeatherService
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getWeatherData(?string $forDate, ?string $endDate = null): ResultSetInterface
    {
        $sql = new Sql($this->adapter, 'weather_data');
        $select = $sql
            ->select()
            ->columns(['humidity', 'temperature', 'timestamp']);

        if ($forDate !== null && $endDate !== null) {
            $select->where(new Between("date(timestamp)", $forDate, $endDate));
        } else {
            if ($forDate !== null) {
                $select->where(new Expression("date(timestamp) = ?", $forDate));
            }
        }

        return $this
            ->adapter
            ->query(
                $sql->buildSqlString($select),
                $this->adapter::QUERY_MODE_EXECUTE
            );
    }
}