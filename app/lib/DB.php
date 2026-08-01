<?php

class DB
{
    private static ?PDO $connection = null;

    public static function connect(array $config): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['name']
        );

        self::$connection = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Pin the session to PHP's timezone offset, so NOW()/CURRENT_TIMESTAMP
        // written by MySQL and time() read by PHP refer to the same clock.
        // Without this the admin inbox shows timestamps skewed by the
        // difference between the MySQL server's timezone and PHP's.
        self::$connection->exec("SET time_zone = '" . self::currentOffset() . "'");

        return self::$connection;
    }

    /**
     * PHP's current UTC offset as a MySQL time_zone string (e.g. "+03:00").
     * Named-zone support (SET time_zone = 'Africa/Dar_es_Salaam') requires the
     * MySQL timezone tables, which are often not loaded on shared hosting —
     * a numeric offset always works.
     */
    private static function currentOffset(): string
    {
        $minutes = (int) (new DateTime('now'))->getOffset() / 60;
        $sign = $minutes < 0 ? '-' : '+';
        $minutes = abs($minutes);

        return sprintf('%s%02d:%02d', $sign, intdiv($minutes, 60), $minutes % 60);
    }
}
