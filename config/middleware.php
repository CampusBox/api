<?php

 
use App\Token;

use Slim\Middleware\JwtAuthentication;
use Slim\Middleware\HttpBasicAuthentication;
use Tuupola\Middleware\Cors;
use Gofabian\Negotiation\NegotiationMiddleware;
use Micheh\Cache\CacheUtil;
use RKA\Middleware\IpAddress;

//$checkProxyHeaders = true;
//$trustedProxies = ['127.0.0.1'];
$container["IpAddress"] = function ($container) {
    return new IpAddress([
        "getip" => function($request, $response, $arguments){
            $ipAddress = $request->withAttribute('ip_address');
            return $response;
        }
        ]);
};

$container["HttpBasicAuthentication"] = function ($container) {
    return new HttpBasicAuthentication([
        "secure" => false,

        "path" => "/any",
        "relaxed" => ["localhost:3000"],
        "users" => [
        "test" => "test"
        ]
        ]);
};

$container["token"] = function ($container) {
    return new Token;
};

$container["JwtAuthentication"] = function ($container) {
    return new JwtAuthentication([
        "secure" => false,
        "path" => ["student", "/studentSkill", "/studentFollow", "/userImage",
                   "/event", "/events/{event_id}", "/rsvpEvent", 
                   "/bookmarkContent", "/appreciateContent", "/addContent", "/addNew", "/contentResponse", "/editDraftContent", "/movetoDraftContent",
                   "/notifications"],
        "passthrough" => [],
        "secret" => getenv("JWT_SECRET"),
        "logger" => $container["logger"],
        "relaxed" => ["192.168.50.52","localhost"],
        "error" => function ($request, $response, $arguments) {
            $data["status"] = "error";
            $data["message"] = $arguments["message"];
            return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        },
        "callback" => function ($request, $response, $arguments) use ($container) {
            $container["token"]->hydrate($arguments["decoded"]);
        }
        ]);
};

$container["Cors"] = function ($container) {
    return new Cors([
        "logger" => $container["logger"],
        "origin" => ["*","192.171.2.213:3000/#!/events"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE","OPTIONS"],
        "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since" ,"Content-Type"],
        "headers.expose" => ["Authorization", "Etag"],
        "credentials" => true,
        "cache" => 60,
        "error" => function ($request, $response, $arguments) {
            $data["status"] = "error";
            $data["message"] = $arguments["message"];
            return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
        ]);
};

$container["Negotiation"] = function ($container) {
    return new NegotiationMiddleware([
        "accept" => ["application/json"]
        ]);
};

$app->add("HttpBasicAuthentication");
$app->add("JwtAuthentication");
$app->add("Cors");
$app->add("Negotiation");
$app->add("IpAddress");

$container["cache"] = function ($container) {
    return new CacheUtil;
};
