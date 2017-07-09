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
	
	$transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";
	$filters = isset($_GET['filters']) ? $_GET['filters'] : "default";
	$limit = isset($_GET['limit']) ? $_GET['limit'] : 4;
	$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;	
	//sort_by can takes price, time_created and many other flags
	$sort_by = isset($_GET['sortby']) ? $_GET['sortby'] : "time_created";
	$sort_order = isset($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
	$valid_sorty_by = ["event_id", "from_time", "to_time", "price"];

	$exists = in_array($sort_by,$valid_sorty_by);
	if (!$exists){
		$sort_by = "time_created";
	}

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	
	if ($token) {
		$username = $token->username;
		if($token->college_id==NULL){
			$college_id = 0;
		}else{
			$college_id = $token->college_id;
		}

	} else{
		$college_id = 0; //Not sure. Should work without this.
		$username = '0';
	}
	
	$events = $this->spot->mapper("App\Event")
	->query("SELECT * FROM `events` "
		."WHERE status = 0 AND (college_id = " . $college_id . " OR audience = 1) "
		."ORDER BY ".$sort_by." ".$sort_order."	" 
		." LIMIT " . $limit ." OFFSET " . $offset);
	
	$offset += $limit;

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);

	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}

	if ($transformer === "mini") {
		$resource = new Collection($events, new EventMiniTransformer(['username' => $username, 'type' => 'get']));
	}
	else {
		$resource = new Collection($events, new EventTransformer(['username' => $username, 'type' => 'get']));	
	}
	
	$data = $fractal->createData($resource)->toArray();
	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;
	$data['meta']['transformer']= $transformer;
	$data['meta']['sortby'] = $sort_by;
	$data['meta']['sortorder'] = $sort_order;

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->get("/events/{event_id}", function ($request, $response, $arguments) {

	$transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));
	
	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	/* Use ETag and date from Event with most recent update. */
	$first = $this->spot->mapper("App\Event")
	->all()
	->where(["status"=>0])
	->order(["time_created" => "DESC"])
	->first();

	/* If-Modified-Since and If-None-Match request header handling. */
	/* Heads up! Apache removes previously set Last-Modified header */
	/* from 304 Not Modified responses. */
	if ($this->cache->isNotModified($request, $response)) {
		return $response->withStatus(304);
	}
	$events = $this->spot->mapper("App\Event")
	->where(['event_id' => $arguments['event_id'], "status"=>0]);
	
	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	if($transformer === "mini"){
		$resource = new Collection($events, new EventMiniTransformer(['username' => $test, 'type' => 'get']));
	}
	else{
		$resource = new Collection($events, new EventTransformer(['username' => $test, 'type' => 'get']));
	}
	$data = $fractal->createData($resource)->toArray();
	$data['meta']['transformer'] = $transformer;
	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->get("/eventsImage/{event_id}", function ($request, $response, $arguments){

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

$app->get("/eventParticipants/{event_id}", function ($request, $response, $arguments) {
	$participants = $this->spot->mapper("App\EventRsvp")->where(["event_id" => $arguments['event_id']]);
	
	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Collection($participants, new EventRsvpTransformer);
	$data = $fractal->createData($resource)->toArray();

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});



$app->post("/event", function ($request, $response, $arguments) {

	$transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";
	$makeStatus = isset($_GET['makestatus']) ? $_GET['makestatus'] : 0;
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
	
	$event['status'] = $makeStatus;

	if(false === $existingEvent = $this->spot->mapper("App\Event")->first(['title' => $event['title'], 'subtitle' => $event['subtitle']])){
		throw new ForbiddenException("Already there", 500);
	}
	else{
	$newEvent = new Event($event);
	$this->spot->mapper("App\Event")->save($newEvent);
	}
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if($transformer === "mini"){
		$resource = new Item($newEvent, new EventMiniTransformer);
	}
	else{
		$resource = new Item($newEvent, new EventTransformer);
	}
	$data = $fractal->createData($resource)->toArray();

	for ($i=0; $i < count($body['tags']); $i++) {
		$tags['event_id'] = $data['data']['id'];
		$tags['name'] = $body['tags'][$i]['name'];
		$intrest = new EventTags($tags);
		$this->spot->mapper("App\EventTags")->save($intrest);
	}

	/* Serialize the response data. */
	if($makeStatus = 2){$data["status"] = 'Saved as draft.';}
	else{$data["status"] = 'Registered Successfully';}
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->patch("/event/{event_id}", function ($request, $response, $arguments) {
	
	$makeStatus = isset($_GET['makestatus']) ? $_GET['makestatus'] : 0;
	$body = $request->getParsedBody();

	if($makeStatus = 0) {
		$event = $this->spot->mapper("App\Event")->first(["event_id" => $arguments["event_id"]]);


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

		$event['status'] = 0;

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

		$data["status"] = $status;
		$data["message"] = "Event published updated";
	}

	if ($makeStatus = 2) {

		// Load existing event using provided id */
		if (false === $event = $this->spot->mapper("App\Event")->first([
			"event_id" => $arguments["event_id"],
			])) {
			throw new NotFoundException("Event not found.", 404);
	}

	// PATCH requires If-Unmodified-Since or If-Match request header to be present. */
			// if (false === $this->cache->hasStateValidator($request)) {
			// 	throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
			// }

	// If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
	// someone has modified the event respond with 412 Precondition Failed. */
			// if (false === $this->cache->hasCurrentState($request, $event->etag(), $event->timestamp())) {
			// 	throw new PreconditionFailedException("Event has been modified.", 412);
			// }

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

		$update_event->status = 2;

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

	$data["status"] = $status;
	$data["message"] = "Event draft updated";
}

return $response->withStatus(200)
->withHeader("Content-Type", "application/json")
->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->put("/events/{event_id}", function ($request, $response, $arguments) {

	$makeStatus = isset($_GET['makestatus']) ? $_GET['makestatus'] : 1;

	/* Load existing event using provided id */
	if (false === $event = $this->spot->mapper("App\Event")->first([
		"event_id" => $arguments["event_id"],
		])) {
		throw new NotFoundException("Event not found.", 404);};

	if ($event->created_by_username != $token->username) {
		throw new ForbiddenException("Only the event owner can perform this action.", 404);
	}

	if ($event->status === 1) {
		throw new ForbiddenException("Event already removed", 404);
	}

	$update_status = $this->spot->mapper("App\Event")->first(["event_id" => $arguments["event_id"]]);

	if ($update_status) {
		$update_status->status = $makeStatus;
		$this->spot->mapper("App\Event")->update($update_status);
	}
	$data["status"] = "ok";
	if($makeStatus = 1)
	$data["message"] = "Event removed";
	else
	$data["message"] = "Event moved to drafts";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
