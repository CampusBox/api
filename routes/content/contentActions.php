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



$app->get("/responses/{content_id}", function ($request, $response, $arguments) {

  $limit = isset($_GET['limit']) ? $_GET['limit'] : 3;
  $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

  $token = $request->getHeader('authorization');
  $token = substr($token[0], strpos($token[0], " ") + 1); 
  $JWT = $this->get('JwtAuthentication');
  $token = $JWT->decodeToken($JWT->fetchToken($request));

  if ($token) 
    $test = $token->username;
  else
    $test = '0';

  if(isset($arguments['content_id'])){
    $responses = $this->spot->mapper("App\ContentResponses")
    ->where(["content_id"=>$arguments['content_id'], "status"=>0])
    ->limit($limit, $offset);
  }else{

    $responses = $this->spot->mapper("App\ContentResponses")
    ->where(["status"=>0])
    ->limit($limit, $offset);
  }
  $offset += $limit;

  /* Serialize the response data. */
  $fractal = new Manager();
  $fractal->setSerializer(new DataArraySerializer);

  if (isset($_GET['include'])) {
    $fractal->parseIncludes($_GET['include']);
  }

  $resource = new Collection($responses, new ContentResponsesTransformer(['username' => $test, 'type' => 'get']));
  $data = $fractal->createData($resource)->toArray();

  $data['meta']['offset'] = $offset;
  $data['meta']['limit'] = $limit;

  return $response->withStatus(200)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->post("/contentResponse/{content_id}", function ($request, $response, $arguments) {

 $body = $request->getParsedBody();

 if(isset($body['response_text'])){

   $contentresponse['username'] =  $this->token->decoded->username;
   $contentresponse['content_id'] = $arguments['content_id'];
   $contentresponse['response_text'] = $body['response_text'];
   $contentresponse['status'] = 0;

   $newresponse = new ContentResponses($contentresponse);
   $this->spot->mapper("App\ContentResponses")->save($newresponse);
   $data["status"] = "ok";
   $data["message"] = "Response added";
   return $response->withStatus(201)
   ->withHeader("Content-Type", "application/json")
   ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
   }
   else{
   $data["status"] = "error";
   $data["message"] = "No response text found";
   return $response->withStatus(406)
   ->withHeader("Content-Type", "application/json")
   ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
   }
});


$app->delete("/contentResponse/{content_response_id}", function ($request, $response, $arguments) {

  $token = $request->getHeader('authorization');
  $token = substr($token[0], strpos($token[0], " ") + 1);  
  $JWT = $this->get('JwtAuthentication');
  $token = $JWT->decodeToken($JWT->fetchToken($request));

  if (!$token) {
    throw new ForbiddenException("Token not found", 404);
  }
  if (false === $contentresponse = $this->spot->mapper("App\ContentResponses")->first([
    "content_response_id" => $arguments["content_response_id"],
    "username" =>  $this->token->decoded->username, "status" => 0
    ])) {
    throw new NotFoundException("Response wasn't there.", 404);
  }

  if ( $contentresponse->username != $token->username)  {
    throw new ForbiddenException("Only the owner can delete the response", 404);
  }

  $update_response = $this->spot->mapper("App\ContentResponses")->first(["content_response_id" => $arguments["content_response_id"]]);

  if ($update_response) {
    $update_response->status = 1;
    $status = $this->spot->mapper("App\ContentResponses")->update($update_response);
  }

  $data["status"] = $status;
  $data["message"] = "Response updated.";
  return $response->withStatus(201)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->patch("/contentResponse/{content_response_id}", function ($request, $response, $arguments) {

  $token = $request->getHeader('authorization');
  $token = substr($token[0], strpos($token[0], " ") + 1);  
  $JWT = $this->get('JwtAuthentication');
  $token = $JWT->decodeToken($JWT->fetchToken($request));

  if (!$token) {
    throw new ForbiddenException("Token not found", 404);
  }
  if (false === $contentresponse = $this->spot->mapper("App\ContentResponses")->first([
    "content_response_id" => $arguments["content_response_id"],
    "username" =>  $this->token->decoded->username, "status" => 0
    ])) {
    throw new NotFoundException("Response wasn't there.", 404);
  }

  if ( $contentresponse->username != $token->username)  {
    throw new ForbiddenException("Only the owner can update the response", 404);
  }

  $body = $request->getParsedBody();

  $update_response = $this->spot->mapper("App\ContentResponses")->first(["content_response_id" => $arguments["content_response_id"]]);

  if ($update_response) {
    $data["orig"] = $update_response->response_text;
    $update_response->response_text = $body['response_text'];
    $data["new"] = $update_response->response_text;
    $status = $this->spot->mapper("App\ContentResponses")->update($update_response);
  }

  $data["status"] = $status;
  $data["message"] = "Response updated.";
  return $response->withStatus(201)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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
  $this->spot->mapper("App\ContentBookmarks")->save($bookmark);

}else {

  throw new NotFoundException("Already Bookmarked", 404);
}

$data["status"] = "ok";
$data["message"] = "New bookmark created";

return $bookmark->withStatus(201)
->withHeader("Content-Type", "application/json")
->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->delete("/bookmarkContent/{content_id}", function ($request, $response, $arguments) {

  /* Load existing bookmark using provided content_id */
  if (false === $bookmark = $this->spot->mapper("App\ContentBookmarks")->first([
    "content_id" => $arguments["content_id"],
    "username" =>  $this->token->decoded->username
    ])) {
    throw new NotFoundException("Had never bookmarked it.", 404);
}
$this->spot->mapper("App\ContentBookmarks")->delete($bookmark);
$data["status"] = "ok";
$data["message"] = "Bookmark Removed";
return $response->withStatus(200)
->withHeader("Content-Type", "application/json")
->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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

  $this->spot->mapper("App\ContentAppreciate")->save($appreciate);
} else  {
  throw new NotFoundException("Already appreciated.", 404);
};
$data["status"] = "ok";
$data["message"] = "Appreciated";
return $response->withStatus(201)
->withHeader("Content-Type", "application/json")
->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->delete("/appreciateContent/{content_id}", function ($request, $response, $arguments) {

 /* Load existing appreciate using provided content_id */
 if (false === $appreciate = $this->spot->mapper("App\ContentAppreciate")->first([
  "content_id" => $arguments["content_id"],
  "username" =>  $this->token->decoded->username
  ])) {
  throw new NotFoundException("Had never appreciateed it.", 404);
};

$this->spot->mapper("App\ContentAppreciate")->delete($appreciate);
$data["status"] = "ok";
$data["message"] = "Appreciation Removed";
return $response->withStatus(200)
->withHeader("Content-Type", "application/json")
->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
