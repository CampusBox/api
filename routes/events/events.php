<?php

use App\Event;
use App\EventTags;
use App\EventTransformer;
use App\EventMiniTransformer;
use App\EventDashboardTransformer;
use Slim\Middleware\JwtAuthentication;
use App\EventRsvp;
use App\EventRsvpTransformer;
use App\EventBookmarks;
use App\EventBookmarksTransformer;
use Exception\ForbiddenException;
use Exception\NotFoundException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Manager;
use League\Fractal\Pagination\Cursor;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;


$app->get("/events", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	$limit = isset($_GET['limit']) ? $_GET['limit'] : 2;
	$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
	if($token){
		$test = $token->username;
		$events = $this->spot->mapper("App\Event")
		->query("SELECT * FROM `events` 
			WHERE status LIKE 'active' AND (college_id = " . $token->college_id . " OR audience = 1)
			ORDER BY CASE 
			WHEN college_id = " . $token->college_id . " THEN college_id
			ELSE audience
			END
			LIMIT " . $limit ." OFFSET " . $offset);
	} else {
		$test = '0';
		$events = $this->spot->mapper("App\Event")
		->query("SELECT * FROM `events` WHERE status LIKE 'active'
			LIMIT " . $limit ." OFFSET " . $offset);
	}

	$offset += $limit;

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);

	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}

	$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();
	
	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;


	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/minievents", function ($request, $response, $arguments) {
	
	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	$limit = isset($_GET['limit']) ? $_GET['limit'] : 4;
	$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

	if ($token) {
		$test = $token->username;
		if($token->college_id==NULL){
			$college_id = 0;
		}else{
			$college_id = $token->college_id;
		}
		$events = $this->spot->mapper("App\Event")
		->query("SELECT * FROM `events` "
			."WHERE status LIKE 'active' AND (college_id = " . $college_id . " OR audience = 1) "
			."ORDER BY CASE "
			."WHEN college_id = " . $college_id . " THEN college_id "
			."ELSE audience "
			."END "
			."LIMIT " . $limit ." OFFSET " . $offset);
	} else{
		$college_id = 0; //Not sure. Should work without this.
		$test = '0';
		$events = $this->spot->mapper("App\Event")
		->where(["status"=>"active"])		
		->query("SELECT * FROM `events`
			LIMIT " . $limit ." OFFSET " . $offset);
	}

	$offset += $limit;

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);

	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}

	$resource = new Collection($events, new EventMiniTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();
	
	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/events", function ($request, $response, $arguments) { 
	$body = $request->getParsedBody(); 

	$limit = isset($_GET['limit']) ? $_GET['limit'] : 3;
	$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

    $type_id =$body['filters']['type']; 
	$audience =$body['filters']['audience']; 

	$token = $request->getHeader('authorization'); 
	$token = substr($token[0], strpos($token[0], " ") + 1);  
	$JWT = $this->get('JwtAuthentication'); 
	$token = $JWT->decodeToken($JWT->fetchToken($request)); 

	if ($token)  
		$test = $token->username; 
	else 
		$test = '0'; 

	if(count($type_id)){ 

		$events = $this->spot->mapper("App\Event") 
		->all() 
		->where(["event_type_id"=>$type_id, "audience"=>$audience, "status"=>"active"]) 
		->limit($limit, $offset) 
		->order(["time_created" => "DESC"]); 
	}else{ 

		$events = $this->spot->mapper("App\Event") 
		->query("SELECT * FROM `events`  
			WHERE status LIKE 'active' AND (college_id = " . $token->college_id . " OR audience = 1) 
			ORDER BY CASE  
			WHEN college_id = " . $token->college_id . " THEN college_id 
			ELSE audience 
			END 
			LIMIT " . $limit ." OFFSET " . $offset); 
	} 

	$offset += $limit; 

	/* Serialize the response data. */ 
	$fractal = new Manager(); 
	$fractal->setSerializer(new DataArraySerializer); 

	if (isset($_GET['include'])) { 
		$fractal->parseIncludes($_GET['include']); 
	} 

	$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get'])); 
	$data = $fractal->createData($resource)->toArray(); 

	$data['meta']['offset'] = $offset; 
	$data['meta']['limit'] = $limit; 


	return $response->withStatus(200) 
	->withHeader("Content-Type", "application/json") 
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)); 
});

$app->get("/eventsTop", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	$currentCursor = 0;
	$previousCursor = 0;
	$limit = 4;
	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	/* Use ETag and date from Event with most recent update. */
	$first = $this->spot->mapper("App\Event")
	->all()
	->where(["status"=>"active"])
	->order(["time_created" => "DESC"])
	->first();

	if ($this->cache->isNotModified($request, $response)) {
		return $response->withStatus(304);
	}

	if(0){

		$events = $this->spot->mapper("App\Event")
		->where(['event_id >' => $currentCursor, "status"=>"active"])
		->limit($limit)
		->order(["time_created" => "DESC"]);
	} else {
		$events = $this->spot->mapper("App\Event")
		->all()
		->where(["status"=>"active"])
		->order(["time_created" => "DESC"]);
	}

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();
	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/eventsDashboard", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	$currentCursor = 0;
	$previousCursor = 0;
	$limit = 2;
	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	if ($this->cache->isNotModified($request, $response)) {
		return $response->withStatus(304);
	}

	if(1){

		$events = $this->spot->mapper("App\Event")
		->query("SELECT * from events WHERE status LIKE 'active' ORDER BY RAND() limit 2"); 

	} else {
		$events = $this->spot->mapper("App\Event")
		->all()
		->where(["status"=>"active"])
		->limit($limit)
		->order(["time_created" => "DESC"]);
	}

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();
	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/eventsImage/{event_id}", function ($request, $response, $arguments) {

	$event = $this->spot->mapper("App\Event")
	->where(["event_id"=>$arguments['event_id']])
	->first();
	if (false === $event) {
		throw new NotFoundException("Event not found.", 404);
	};

	$new_data=explode(";",$event->image);
	$type=$new_data[0];
	$data=explode(",",$new_data[1]);

	return $response->withStatus(200)
	->withHeader("Content-Type", $type)
	->write(base64_decode($data[1]));
});

$app->get("/event/{event_id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	$currentCursor = 3;
	$previousCursor = 2;
	$limit = 20;
	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	/* Use ETag and date from Event with most recent update. */
	$first = $this->spot->mapper("App\Event")
	->all()
	->where(["status"=>"active"])
	->order(["time_created" => "DESC"])
	->first();

	/* If-Modified-Since and If-None-Match request header handling. */
	/* Heads up! Apache removes previously set Last-Modified header */
	/* from 304 Not Modified responses. */
	if ($this->cache->isNotModified($request, $response)) {
		return $response->withStatus(304);
	}
	$events = $this->spot->mapper("App\Event")
	->where(['event_id' => $arguments['event_id'], "status"=>"active"])
	->limit(1);
	
	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();
	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/eventsFilter", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	$currentCursor = 3;
	$previousCursor = 2;
	$limit = 20;
	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	/* Use ETag and date from Event with most recent update. */
	$first = $this->spot->mapper("App\Event")
	->all()
	->order(["time_created" => "DESC"])
	->first();

	/* Add Last-Modified and ETag headers to response when atleast on event exists. */
	if ($first) {
		$response = $this->cache->withEtag($response, $first->etag());
		$response = $this->cache->withLastModified($response, $first->timestamp());
	}

	/* If-Modified-Since and If-None-Match request header handling. */
	/* Heads up! Apache removes previously set Last-Modified header */
	/* from 304 Not Modified responses. */
	if ($this->cache->isNotModified($request, $response)) {
		return $response->withStatus(304);
	}

	if($currentCursor){

		$events = $this->spot->mapper("App\Event")
		->where($body)
		->limit($limit)
		->order(["time_created" => "DESC"]);
	} else {
		$events = $this->spot->mapper("App\Event")
		->limit($limit)
		->get();
	}

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();
	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/addEvent", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$event['college_id'] =  isset($this->token->decoded->college_id)?:0;
	$event['created_by_username'] =  $this->token->decoded->username;
	$event['title'] = $body['event']['title'];
	$event['subtitle'] = $body['event']['subtitle'];
	$event['image'] = $body['event']['croppedDataUrl'];
	$event['price'] = $body['event']['price'];
	$event['description'] = $body['event']['description'];
	//$event['contactperson1'] = $body['event']['contactperson1'];
	$event['venue'] = $body['event']['venue'];
	$event['audience'] = $body['event']['audience'];
	$event['event_type_id'] = (int)$body['event']['type'];
	$event['event_category_id'] = isset($body['event']['category']) ? (int)$body['event']['category']:0;
	$event['link'] = $body['event']['link'];
	$event['organiser_name'] = $body['event']['organiserName'];
	$event['organiser_phone'] = (int)$body['event']['organiserPhone'];
	$event['organiser_link'] = $body['event']['organiserLink'];
	
	$event['to_date'] = $body['event']['toDate'];
	$event['to_time'] = $body['event']['toTime'];
	$event['to_period'] = $body['event']['toPeriod']=="am"?0:1;
	$event['from_date'] = $body['event']['fromDate'];
	$event['from_time'] = $body['event']['fromTime'];
	$event['from_period'] = $body['event']['fromPeriod']=="am"?0:1;
	$event['city'] = $body['event']['city'];
	$event['state'] = isset($body['event']['state'])?:null;

	$event['status'] = "active";

	$newEvent = new Event($event);
	$this->spot->mapper("App\Event")->save($newEvent);

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($newEvent, new EventTransformer);
	$data = $fractal->createData($resource)->toArray();

	for ($i=0; $i < count($body['tags']); $i++) {
		$tags['event_id'] = $data['data']['id'];
		$tags['name'] = $body['tags'][$i]['name'];
		$intrest = new EventTags($tags);
		$this->spot->mapper("App\EventTags")->save($intrest);
	}

	/* Serialize the response data. */
	$data["status"] = 'Registered Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/eventParticipants/{id}", function ($request, $response, $arguments) {
	$participants = $this->spot->mapper("App\EventRsvp")->where(["event_id" => $arguments['id']]);
	
	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Collection($participants, new EventRsvpTransformer);
	$data = $fractal->createData($resource)->toArray();

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch("/events/{id}", function ($request, $response, $arguments) {

	/* Load existing event using provided id */
	if (false === $event = $this->spot->mapper("App\Event")->first([
		"event_id" => $arguments["id"],
		])) {
		throw new NotFoundException("Event not found.", 404);
    };

	/* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
		// if (false === $this->cache->hasStateValidator($request)) {
		// 	throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
		// }

	/* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
	/* someone has modified the event respond with 412 Precondition Failed. */
		// if (false === $this->cache->hasCurrentState($request, $event->etag(), $event->timestamp())) {
		// 	throw new PreconditionFailedException("Event has been modified.", 412);
		// }

	$body = $request->getParsedBody();
	$event->data($body);
	$this->spot->mapper("App\Event")->save($event);

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($event, new EventTransformer);
	$data = $fractal->createData($resource)->toArray();
	$data["status"] = "ok";
	$data["message"] = "Event updated";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put("/events/{id}", function ($request, $response, $arguments) {
    
    $token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 401);
	}

	/* Load existing event using provided id */
	if (false === $event = $this->spot->mapper("App\Event")->first([
		"event_id" => $arguments["id"], "status" => "active",
		])) {
			throw new NotFoundException("Event not found.", 404);
	};

	if ($event->created_by_username != $token->username) {
		throw new ForbiddenException("Only the owner can delete the event", 404);
	}

	/* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
	/* someone has modified the event respond with 412 Precondition Failed. */
	if (false === $this->cache->hasCurrentState($request, $event->etag(), $event->timestamp())) {
		throw new PreconditionFailedException("Event has been modified.", 412);
	}

	$body = $request->getParsedBody();

	/* PUT request assumes full representation. If any of the properties is */
	/* missing set them to default values by clearing the event object first. */
	$event->clear();
	$event->data($body);
	$this->spot->mapper("App\Event")->save($event);

	/* Add Last-Modified and ETag headers to response. */
	$response = $this->cache->withEtag($response, $event->etag());
	$response = $this->cache->withLastModified($response, $event->timestamp());

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($event, new EventTransformer);
	$data = $fractal->createData($resource)->toArray();
	$data["status"] = "ok";
	$data["message"] = "Event updated";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->delete("/delEvent/{event_id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 401);
	}

	/* Load existing event using provided id */
	if (false === $event = $this->spot->mapper("App\Event")->first([
		"event_id" => $arguments["event_id"],
		])) {
		throw new NotFoundException("Event not found.", 404);};

	if ($event->created_by_username != $token->username) {
		throw new ForbiddenException("Only the owner can delete the event", 404);
	}

	if ($event->status === "inactive") {
		throw new ForbiddenException("Event already removed", 404);
	}

	$update_status = $this->spot->mapper("App\Event")->first(["event_id" => $arguments["event_id"]]);

	if ($update_status) {
		$update_status->status = "inactive";
		$this->spot->mapper("App\Event")->update($update_status);
	}
	$data["status"] = "ok";
	$data["message"] = "Event removed";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/newDraftEvent", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$event['college_id'] =  isset($this->token->decoded->college_id)?:0;
	$event['created_by_username'] =  $this->token->decoded->username;
	$event['title'] = $body['event']['title'];
	$event['subtitle'] = $body['event']['subtitle'];
	$event['image'] = $body['event']['croppedDataUrl'];
	$event['price'] = $body['event']['price'];
	$event['description'] = $body['event']['description'];
	//$event['contactperson1'] = $body['event']['contactperson1'];
	$event['venue'] = $body['event']['venue'];
	$event['audience'] = $body['event']['audience'];
	$event['event_type_id'] = (int)$body['event']['type'];
	$event['event_category_id'] = isset($body['event']['category']) ? (int)$body['event']['category']:0;
	$event['link'] = $body['event']['link'];
	$event['organiser_name'] = $body['event']['organiserName'];
	$event['organiser_phone'] = (int)$body['event']['organiserPhone'];
	$event['organiser_link'] = $body['event']['organiserLink'];
	
	$event['to_date'] = $body['event']['toDate'];
	$event['to_time'] = $body['event']['toTime'];
	$event['to_period'] = $body['event']['toPeriod']=="am"?0:1;
	$event['from_date'] = $body['event']['fromDate'];
	$event['from_time'] = $body['event']['fromTime'];
	$event['from_period'] = $body['event']['fromPeriod']=="am"?0:1;
	$event['city'] = $body['event']['city'];
	$event['state'] = isset($body['event']['state'])?:null;

	$event['status'] = "draft";

	$newEvent = new Event($event);
	$this->spot->mapper("App\Event")->save($newEvent);

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($newEvent, new EventTransformer);
	$data = $fractal->createData($resource)->toArray();

	for ($i=0; $i < count($body['tags']); $i++) {
		$tags['event_id'] = $data['data']['id'];
		$tags['name'] = $body['tags'][$i]['name'];
		$intrest = new EventTags($tags);
		$this->spot->mapper("App\EventTags")->save($intrest);
	}

	/* Serialize the response data. */
	$data["status"] = 'Saved Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete("/movetoDraftEvent/{event_id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 401);
	}

	/* Load existing event using provided id */
	if (false === $event = $this->spot->mapper("App\Event")->first([
		"event_id" => $arguments["event_id"],
		])) {
		throw new NotFoundException("Event not found.", 404);};

	if ($event->created_by_username != $token->username) {
		throw new ForbiddenException("Only the owner can move the event to draft", 404);
	}

	if ($event->status === "draft") {
		throw new ForbiddenException("Event already moved to draft", 404);
	}

	$update_status = $this->spot->mapper("App\Event")->first(["event_id" => $arguments["event_id"]]);

	if ($update_status) {
		$update_status->status = "draft";
		$this->spot->mapper("App\Event")->update($update_status);
	}
	$data["status"] = "ok";
	$data["message"] = "Event successfully moved to draft.";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->post("/editDraftEvent/{event_id}", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();


	$update_event = $this->spot->mapper("App\Event")->first(["event_id" => $arguments["event_id"]]);
	
	if ($update_event) {

		$update_event->college_id =  isset($this->token->decoded->college_id)?:0;
		$update_event->created_by_username =  $this->token->decoded->username;
		$update_event->title = $body['event']['title'];
		$update_event->subtitle = $body['event']['subtitle'];
		$update_event->image = $body['event']['croppedDataUrl'];
		$update_event->price = $body['event']['price'];
		$update_event->description = $body['event']['description'];
	//$update_event->contactperson1 = $body['event']['contactperson1'];
		$update_event->venue = $body['event']['venue'];
		$update_event->audience = $body['event']['audience'];
		$update_event->event_type_id = (int)$body['event']['type'];
		$update_event->event_category_id = isset($body['event']['category']) ? (int)$body['event']['category']:0;
		$update_event->link = $body['event']['link'];
		$update_event->organiser_name = $body['event']['organiserName'];
		$update_event->organiser_phone = (int)$body['event']['organiserPhone'];
		$update_event->organiser_link = $body['event']['organiserLink'];

		$update_event->to_date = $body['event']['toDate'];
		$update_event->to_time = $body['event']['toTime'];
		$update_event->to_period = $body['event']['toPeriod']=="am"?0:1;
		$update_event->from_date = $body['event']['fromDate'];
		$update_event->from_time = $body['event']['fromTime'];
		$update_event->from_period = $body['event']['fromPeriod']=="am"?0:1;
		$update_event->city = $body['event']['city'];
		$update_event->state = isset($body['event']['state'])?:null;

		$update_event->status = "active";

		$this->spot->mapper("App\Event")->update($update_event);
	}

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($update_event, new EventTransformer);
	$data = $fractal->createData($resource)->toArray();

	for ($i=0; $i < count($body['tags']); $i++) {
		$tags['event_id'] = $data['data']['id'];
		$tags['name'] = $body['tags'][$i]['name'];
		$intrest = new EventTags($tags);
		$this->spot->mapper("App\EventTags")->save($intrest);
	}

	/* Serialize the response data. */
	$data["status"] = 'ok';
	$data["status"] = 'Saved Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
