<?php

 

use App\Event;
use App\ReportContent;
use App\ReportEvent;
use App\ReportEventTransformer;
use App\ReportContentTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->post("/report/event/{id}", function ($request, $response, $arguments) {

    /* Load existing report using provided id */
    if (false === $report = $this->spot->mapper("App\Event")->first([
        "event_id" => $arguments["id"]
    ])){
        throw new NotFoundException("Event not found.", 404);
    };
    if (!(false === $report = $this->spot->mapper("App\ReportEvent")->first([
        "event_id" => $arguments["id"]
    ]))){
        $data["message"]= "Already Reported";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    $body = $request->getParsedBody();
    $reportdata["event_id"]=$arguments["id"];
    $reportdata["remarks"]=isset($body["remark"])?$body["remark"]:null;
    $reportdata["username"]=$token->username;
    $report = new ReportEvent($reportdata);
    $this->spot->mapper("App\ReportEvent")->save($report);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($report, new ReportEventTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New report created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->post("/report/content/{id}", function ($request, $response, $arguments) {

    /* Load existing report using provided id */
    if (false === $report = $this->spot->mapper("App\Content")->first([
        "content_id" => $arguments["id"]
    ])){
        throw new NotFoundException("Content not found.", 404);
    };
    if (!(false === $report = $this->spot->mapper("App\ReportContent")->first([
        "content_id" => $arguments["id"]
    ]))){
        $data["message"]= "Already Reported";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    $body = $request->getParsedBody();
    $reportdata["content_id"]=$arguments["id"];
    $reportdata["remarks"]=isset($body["remark"])?$body["remark"]:null;
    $reportdata["username"]=$token->username;
    $report = new ReportContent($reportdata);
    $this->spot->mapper("App\ReportContent")->save($report);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($report, new ReportContentTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New report created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});