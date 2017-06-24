<?php

use App\Event;
use App\EventBookmarks;
use App\EventRsvp;
use Exception\ForbiddenException;
use Exception\NotFoundException;
use Slim\Middleware\JwtAuthentication;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;


$app->post("/bookmarkEvent/{event_id}", function ($request, $response, $arguments) {

 $body = [
 "username" => $this->token->decoded->username,
 "event_id" => $arguments["event_id"]
 ];
 $bookmark = new EventBookmarks($body);
 if (false === $check = $this->spot->mapper("App\EventBookmarks")->first([
                                                                         "event_id" => $arguments["event_id"],
                                                                         "username" =>  $this->token->decoded->username
                                                                         ])) 
 {

  $id = $this->spot->mapper("App\EventBookmarks")->save($bookmark);
  if ($id) {

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $bookmark->etag());
    $response = $this->cache->withLastModified($response, $bookmark->timestamp());
    $data["status"] = "ok";
    $data["message"] = "New bookmark created.";

    return $response->withStatus(201)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  } else {

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $bookmark->etag());
    $response = $this->cache->withLastModified($response, $bookmark->timestamp());
    $data["status"] = "error";
    $data["message"] = "Error in bookmarking!";

    return $response->withStatus(500)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  }
}
else
{
  throw new NotFoundException("Already Bookmarked", 403);
}
});


$app->delete("/bookmarkEvent/{event_id}", function ($request, $response, $arguments) {

  /* Load existing bookmark using provided event_id */
  if (false === $bookmark = $this->spot->mapper("App\EventBookmarks")->first([
                                                                             "event_id" => $arguments["event_id"],
                                                                             "username" =>  $this->token->decoded->username
                                                                             ])) {
    throw new NotFoundException("Had never bookmarked it.", 404);
}
$id = $this->spot->mapper("App\EventBookmarks")->delete($bookmark);
if ($id) {

 $data["status"] = "ok";
 $data["message"] = "Bookmark Removed.";

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

/**
 * Modifies RSVP state of a user
 * 0 - Default state (not going & not interested)
 * 1 - Going
 * 2 - Interested
 *
 * If a state already exists and
 *       IF 0 is sent: item is deleted
 *       else: update item if different
 * Else
 *       If 0 is sent: NotFoundException
 *       Else: create item
 */
$app->post("/rsvpEvent/{event_id}/{state}", function ($request, $response, $arguments) {

 $state = $arguments["state"];
 $item = $this->spot->mapper("App\EventRsvp")->first([
                                                     "event_id" => $arguments["event_id"],
                                                     "username" =>  $this->token->decoded->username
                                                     ]);
 
 $data["state"] = $state;

 if ($item) {     // Item exists

   if($state == 0) { // Delete RSVP

    $status = $this->spot->mapper("App\EventRsvp")->delete($item);
    $data["status"] = $status;
    if ($status) 
      $data["message"] = "Rsvp Removed";

  } else {

    $state = ($arguments["state"] == 1);

    if ($item->state != $state) {  // New state is different than the current saved

      $item->state = $state;
      $status = $this->spot->mapper("App\EventRsvp")->update($item);

      $data["message"] = "RSVP updated";

    }  else                 // No change in state
    $data["message"] = "No change";
  }

} else{ // Item doesn;t exist

  if ($state == 0) {
    throw new NotFoundException("Had never rsvped it.", 404);
  } else {

    $body = [
    "username" => $this->token->decoded->username,
    "event_id" => $arguments["event_id"],
    "state" => $state
    ];
    $rsvp = new EventRsvp($body);
    $status = $this->spot->mapper("App\EventRsvp")->save($rsvp);

    $data["status"] = $status;
    if ($status) 
      $data["message"] = "RSVP added";
  }
}

// $body = [
// "username" => $this->token->decoded->username,
// "event_id" => $arguments["event_id"],
// "state" => $state
// ];
// $rsvp = new EventRsvp($body);

// if ($item ) {
//   $data['orig'] = $item->state;
//   if ($item->state != $state) {

//     $item->state = $state;
//     $status = $this->spot->mapper("App\EventRsvp")->update($item);

//     $data["message"] = "RSVP updated";

//   }else  {
//     $data["message"] = "No change";
//   }
// } else{
//   $status = $this->spot->mapper("App\EventRsvp")->save($rsvp);

//   $data["message"] = "RSVP added";
// }


return $response->withStatus(201)
->withHeader("Content-Type", "application/json")
->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->delete("/rsvpEvent/{event_id}", function ($request, $response, $arguments) {

 /* Load existing rsvp using provided event_id */
 if (false === $rsvp = $this->spot->mapper("App\EventRsvp")->first([
                                                                   "event_id" => $arguments["event_id"],
                                                                   "username" =>  $this->token->decoded->username
                                                                   ])) {
  throw new NotFoundException("Had never rsvped it.", 404);
};
$id = $this->spot->mapper("App\EventRsvp")->delete($rsvp);
if ($id) {

 $data["status"] = "ok";
 $data["message"] = "Rsvp Removed.";

 return $response->withStatus(200)
 ->withHeader("Content-Type", "application/json")
 ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
} else {

  $data["status"] = "error";
  $data["message"] = "Error removing Rsvp!";

  return $response->withStatus(500)
  ->withHeader("Content-Type", "application/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
});
