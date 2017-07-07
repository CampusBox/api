<?php

use App\Content;
use App\ContentResponses;
use App\ContentBookmarks;
use App\ContentAppreciate;
use Exception\NotFoundException;
use Exception\ForbiddenException;
use App\ContentResponsesTransformer;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use Slim\Middleware\JwtAuthentication;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;


/**
 * Gets 'limit' number of responses of given content showing latest first
 * Arguments: content_id - Id of the content you want to fetch responses of
 *            limit (optional default-3) - The number of responses you want to get
 *            offset (optional default-0) - The offset after which the responses are needed
 */
$app->get("/responses/{content_id}", function ($request, $response, $arguments) {

  $limit = isset($_GET['limit']) ? $_GET['limit'] : 3;
  $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

  $responses = $this->spot->mapper("App\ContentResponses")
  ->where(["content_id"=>$arguments['content_id'], "status"=>0])
  ->limit($limit, $offset)
  ->order(["content_response_id" => "DESC"]);

  $offset += $limit;

  /* Serialize the response data. */
  $fractal = new Manager();
  $fractal->setSerializer(new DataArraySerializer);

  $resource = new Collection($responses, new ContentResponsesTransformer());
  $data = $fractal->createData($resource)->toArray();

  $data['meta']['offset'] = $offset;
  $data['meta']['limit'] = $limit;

  return $response->withStatus(200)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

/**
 * Adds a new response to the content with given id
 * Arguments: content_id- Id of the content you want to add response to
 * 
 * Body: response_text [type: String] - The comment you want to add
 *
 * Token is compulsary for this
 */
$app->post("/contentResponse/{content_id}", function ($request, $response, $arguments) {

 $body = $request->getParsedBody();

 if(isset($body['response_text'])){

   $contentresponse['username'] =  $this->token->decoded->username;
   $contentresponse['content_id'] = $arguments['content_id'];
   $contentresponse['response_text'] = $body['response_text'];
   $contentresponse['status'] = 0;

   $newresponse = new ContentResponses($contentresponse);
   $mapper = $this->spot->mapper("App\ContentResponses");
   $id = $mapper->save($newresponse);

   if ($id) {

     /* Serialize the response data. */
     $fractal = new Manager();
     $fractal->setSerializer(new DataArraySerializer);

     $entity = $mapper->where(["content_response_id"=>$id, "status"=>0]);

     $data["status"] = "ok";
     $data["message"] = "Response added";

     $resource = new Collection($entity, new ContentResponsesTransformer());
     $data["response"] = $fractal->createData($resource)->toArray()['data'][0];

     return $response->withStatus(201)
     ->withHeader("Content-Type", "application/json")
     ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
   } else {

    $data["status"] = "error";
    $data["message"] = "Error in inserting!";

    return $response->withStatus(500)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  }


}
else{
 $data["status"] = "error";
 $data["message"] = "No response text found";
 return $response->withStatus(406)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
});

/**
 * Deactivates the given response (Set status to 1 i.e Inactive)
 * Arguments: content_response_id - Id of the response you want to deactivate
 *
 * Token is required for this action
 * 
 * Only the owner of the comment can perform this action
 */
$app->delete("/contentResponse/{content_response_id}", function ($request, $response, $arguments) {

  if (false === $contentresponse = $this->spot->mapper("App\ContentResponses")->first([
                                                                                      "content_response_id" => $arguments["content_response_id"],
                                                                                      "username" =>  $this->token->decoded->username, "status" => 0
                                                                                      ])) {
    throw new NotFoundException("Response wasn't there.", 404);
}

$update_response = $this->spot->mapper("App\ContentResponses")->first(["content_response_id" => $arguments["content_response_id"]]);

if ($update_response) {
  $update_response->status = 1;
  $status = $this->spot->mapper("App\ContentResponses")->update($update_response);

if ($status) {

 $data["status"] = "ok";
 $data["message"] = "Response deleted successfully.";

 return $response->withStatus(200)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
} else {

  $data["status"] = "error";
  $data["message"] = "Error deleting response!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
}
});

/**
 * Updates an existing response
 * Arguments: content_response_id- Id of the response you want to edit
 * 
 * Body: response_text [type: String] - The response you want to edit
 *
 * Token is compulsary for this
 *
 * Only the owner of the response can perform this action
 */
$app->patch("/contentResponse/{content_response_id}", function ($request, $response, $arguments) {

  if (false === $contentresponse = $this->spot->mapper("App\ContentResponses")->first([
                                                                                      "content_response_id" => $arguments["content_response_id"],
                                                                                      "username" =>  $this->token->decoded->username, "status" => 0
                                                                                      ])) {
    throw new NotFoundException("Response wasn't there.", 404);
}

$body = $request->getParsedBody();

$update_response = $this->spot->mapper("App\ContentResponses")->first(["content_response_id" => $arguments["content_response_id"]]);

if ($update_response) {
  $data["orig"] = $update_response->response_text;
  $update_response->response_text = $body['response_text'];
  $data["new"] = $update_response->response_text;
  $status = $this->spot->mapper("App\ContentResponses")->update($update_response);

 if ($status) {

 $data["status"] = "ok";
 $data["message"] = "Response updated successfully.";

 return $response->withStatus(200)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
} else {

  $data["status"] = "error";
  $data["message"] = "Error updating response!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
}
});


$app->post("/bookmarkContent/{content_id}", function ($request, $response, $arguments) {

 $body = [
 "username" => $this->token->decoded->username,
 "content_id" => $arguments["content_id"]
 ];

 $bookmark = new ContentBookmarks($body);

 if (false === $check = $this->spot->mapper("App\ContentBookmarks")->first([
                                                                           "content_id" => $arguments["content_id"],
                                                                           "username" =>  $this->token->decoded->username
                                                                           ])) {
  $id = $this->spot->mapper("App\ContentBookmarks")->save($bookmark);

if ($id) {

 $data["status"] = "ok";
 $data["message"] = "New bookmark created.";

 return $response->withStatus(201)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
} else {

  $data["status"] = "error";
  $data["message"] = "Error in bookmarking!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

}else {

  throw new NotFoundException("Already Bookmarked!", 404);
}
});


$app->delete("/bookmarkContent/{content_id}", function ($request, $response, $arguments) {

  /* Load existing bookmark using provided content_id */
  if (false === $bookmark = $this->spot->mapper("App\ContentBookmarks")->first([
                                                                               "content_id" => $arguments["content_id"],
                                                                               "username" =>  $this->token->decoded->username
                                                                               ])) {
    throw new NotFoundException("Had never bookmarked it.", 404);
}
$id = $this->spot->mapper("App\ContentBookmarks")->delete($bookmark);

if ($id) {

 $data["status"] = "ok";
 $data["message"] = "Bookmark deleted successfully.";

 return $response->withStatus(200)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
} else {

  $data["status"] = "error";
  $data["message"] = "Error deleting bookmark!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
});


$app->post("/appreciateContent/{content_id}", function ($request, $response, $arguments) {

 $body = [
 "username" => $this->token->decoded->username,
 "content_id" => $arguments["content_id"]
 ];

 $appreciate = new ContentAppreciate($body);

 if (false === $check = $this->spot->mapper("App\ContentAppreciate")->first([
                                                                            "content_id" => $arguments["content_id"],
                                                                            "username" =>  $this->token->decoded->username
                                                                            ])) {

   $id = $this->spot->mapper("App\ContentAppreciate")->save($appreciate);

 if ($id) {

   $data["status"] = "ok";
   $data["message"] = "Appreciated.";

   return $response->withStatus(201)
   ->withHeader("Content-Type", "application/json")
   ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
 } else {

  $data["status"] = "error";
  $data["message"] = "Error in appreciating.!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
} else  {
  throw new NotFoundException("Already appreciated.", 404);
};
});


$app->delete("/appreciateContent/{content_id}", function ($request, $response, $arguments) {

 /* Load existing appreciate using provided content_id */
 if (false === $appreciate = $this->spot->mapper("App\ContentAppreciate")->first([
                                                                                 "content_id" => $arguments["content_id"],
                                                                                 "username" =>  $this->token->decoded->username
                                                                                 ])) {
  throw new NotFoundException("Had never appreciated it.", 404);
};

$id = $this->spot->mapper("App\ContentAppreciate")->delete($appreciate);

if ($id) {

 $data["status"] = "ok";
 $data["message"] = "Appreciation Removed.";

 return $response->withStatus(200)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
} else {

  $data["status"] = "error";
  $data["message"] = "Error removing appreciation!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
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