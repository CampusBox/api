<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

date_default_timezone_set("UTC");
require __DIR__ . "/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ],
        'debug' => true
]);
 
require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";

$app->get("/", function ($request, $response, $arguments) {
    print "Here be dragons";
});

require __DIR__ . "/routes/token.php";
require __DIR__ . "/routes/todos.php";
require __DIR__ . "/routes/events.php";
require __DIR__ . "/routes/reports.php";
require __DIR__ . "/routes/colleges.php";
require __DIR__ . "/routes/skills.php";
require __DIR__ . "/routes/students.php";

// require __DIR__ . "/routes/logins.php";
// require __DIR__ . "/routes/teachers.php";


$app->run();
