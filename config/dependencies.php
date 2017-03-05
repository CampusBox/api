<?php

 

$container = $app->getContainer();

use Spot\Config;
use Spot\Locator;
use Doctrine\DBAL\Logging\MonologSQLLogger;

$container["spot"] = function ($container) {

    $config = new Config();
    $mysql = $config->addConnection("mysql", [
        "dbname" => getenv("DB_NAME"),
        "user" => getenv("DB_USER"),
        "password" => getenv("DB_PASSWORD"),
        "host" => getenv("DB_HOST"),
        "driver" => "pdo_mysql",
        "charset" => "utf8"
    ]);

    $spot = new Locator($config);

    $logger = new MonologSQLLogger($container["logger"]);
    $mysql->getConfiguration()->setSQLLogger($logger);

    return $spot;
};

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Monolog\Formatter\LineFormatter;

$container = $app->getContainer();

$container["logger"] = function ($container) {
    $logger = new Logger("slim");

    $formatter = new LineFormatter(
        "[%datetime%] [%level_name%]: %message% %context%\n",
        null,
        true,
        true
    );

    /* Log to timestamped files */
    $rotating = new RotatingFileHandler(__DIR__ . "/../logs/slim.log", 0, Logger::DEBUG);
    $rotating->setFormatter($formatter);
    $logger->pushHandler($rotating);

    return $logger;
};
