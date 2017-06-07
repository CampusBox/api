<?php

use App\Content;
use App\ContentItems;
use App\ContentType;
use App\ContentTags;
use App\ContentTransformer;
use App\ContentItemsTransformer;
use App\ContentMiniTransformer;
use App\ContentAppreciateTransformer;
use Exception\ForbiddenException;
use Exception\NotFoundException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

$app->get("/contentSorted", function ($request, $response, $arguments) {
	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 404);
	}

	$user_college_id = $token->college_id;
	$username= $token->username;
	$content = $this->spot->mapper("App\Content")
	->query("SELECT
	        contents.content_id,
	        contents.created_by_username,
	        contents.timer,
	        contents.college_id,
	        contents.title,
	        contents.content_type_id,
	        student_interests.username ,
	        count(content_appreciates.content_id) as likes,

	        CASE WHEN (student_interests.username = '".$username."') 
	        THEN 6 ELSE 0 END AS interestScore,

	        CASE WHEN (contents.college_id = ".$user_college_id.") 
	        THEN 3 ELSE 0 END AS interScore,

	        CASE WHEN (followers.follower_username = '".$username."') 
	        THEN 0 ELSE 8 END AS followScore,

	        CASE WHEN content_appreciates.content_id IS NULL 
	        THEN 0 ELSE LOG(COUNT(content_appreciates.content_id))  END AS appreciateScore

	        FROM contents

	        LEFT JOIN student_interests
	        ON  contents.content_type_id =student_interests.interest_id 

	        LEFT JOIN followers
	        ON contents.created_by_username = followers.followed_username

	        LEFT JOIN content_appreciates
	        ON contents.content_id = content_appreciates.content_id

	        GROUP BY contents.content_id
	        ORDER BY interestScore+interScore+followScore DESC,contents.timer 
	        LIMIT 3 OFFSET 0
	        ;");

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});
$app->get("/contents[/{content_type_id}]", function ($request, $response, $arguments) {

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

	if(isset($arguments['content_type_id'])){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id']])
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}
	$offset += $limit;

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($contents, new ContentTransformer([ 'type' => 'get', 'username' => $test]));
	$data = $fractal->createData($resource)->toArray();
	
	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->post("/contents", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$limit = $body['limit'];
	$offset = $body['offset'];
	$filters = $body['filters'];

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	if(count($filters)){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$filters])
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}
	$offset += $limit;

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($contents, new ContentTransformer([ 'type' => 'get', 'username' => $test]));
	$data = $fractal->createData($resource)->toArray();
	
	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get("/contentsDashboard", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	if(isset($arguments['content_type_id'])){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id']])
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->limit(6)
		->order(["timer" => "DESC"]);
	}

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($contents, new ContentTransformer([ 'type' => 'get', 'username' => $test]));
	$data = $fractal->createData($resource)->toArray();

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get("/contentsList", function ($request, $response, $arguments) {

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

	if(isset($arguments['content_type_id'])){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id']])
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($contents, new ContentMiniTransformer([ 'type' => 'get', 'username' => $test]));
	$data = $fractal->createData($resource)->toArray();

	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get("/contentsImage/{content_item_id}", function ($request, $response, $arguments) {

	$content = $this->spot->mapper("App\ContentItems")
	->where(["content_item_id"=>$arguments['content_item_id']])
	->first();

	if (false === $content) {
		throw new NotFoundException("Content not found.", 404);
	};

	$new_data=explode(";",$content->image);
	$type=$new_data[0];
	$data=explode(",",$new_data[1]);

	return $response->withStatus(200)
	->withHeader("Content-Type", $type)
	->write(base64_decode($data[1]));
});
$app->get("/contentAppreciates/{content_id}", function ($request, $response, $arguments) { 

	$appreciates = $this->spot->mapper("App\ContentAppreciate") 
	->all() 
	->where(["content_id"=>$arguments['content_id']]); 

	/* Serialize the response data. */ 
	$fractal = new Manager(); 
	$fractal->setSerializer(new DataArraySerializer); 
	if (isset($_GET['include'])) { 
		$fractal->parseIncludes($_GET['include']); 
	} 
	$resource = new Collection($appreciates, new ContentAppreciateTransformer()); 
	$data = $fractal->createData($resource)->toArray(); 

	return $response->withStatus(200) 
	->withHeader("Content-Type", "application/json") 
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)); 
}); 
$app->get("/contentsRandom", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	
	$contents = $this->spot->mapper("App\Content")
	->query("SELECT * from contents ORDER BY RAND() limit 3"); 

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($contents, new ContentTransformer([ 'type' => 'get', 'username' => $test]));
	$data = $fractal->createData($resource)->toArray();

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get("/contentsTop[/{content_type_id}]", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if ($token) 
		$test = $token->username;
	else
		$test = '0';

	/* Use ETag and date from Content with most recent update. */
	if(isset($arguments['content_type_id'])){

		$first = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id']])
		->order(["timer" => "DESC"])
		->first();

	}else{

		$first = $this->spot->mapper("App\Content")
		->all()
		->order(["timer" => "DESC"])
		->first();
	}

	if(isset($arguments['content_type_id'])){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id']])
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->order(["timer" => "DESC"]);
	}

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	if (isset($_GET['include'])) {
		$fractal->parseIncludes($_GET['include']);
	}
	$resource = new Collection($contents, new ContentTransformer([ 'type' => 'get', 'username' => $test]));
	$data = $fractal->createData($resource)->toArray();

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

/*

 $app->post("/contents", function ($request, $response, $arguments) {

	
	if (true === $this->token->hasScope(["content.all", "content.create"])) {
		throw new ForbiddenException("Token not allowed to create contents.", 403);
	}

	$body = $request->getParsedBody();

	$content = new Content($body);
	$this->spot->mapper("App\Content")->save($content);

	
	$response = $this->cache->withEtag($response, $content->etag());
	$response = $this->cache->withLastModified($response, $content->timestamp());

	
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($content, new ContentTransformer);
	$data = $fractal->createData($resource)->toArray();
	$data["status"] = "ok";
	$data["message"] = "New content created";

	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->withHeader("Location", $data["data"]["links"]["self"])
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
*/

$app->get("/content/{id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if ($token) 
		$test = $token->username;
	else
		$test = '0';
	/* Load existing content using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first([
	                                                                   "content_id" => $arguments["id"],
	                                                                   ])) 
	{
		throw new NotFoundException("Content not found.", 404);
	};

	/* Serialize the response data. */
	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($content, new ContentTransformer(['username' => $test, 'type' => 'get']));
	$data = $fractal->createData($resource)->toArray();

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/addContent", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$content['created_by_username'] =  $this->token->decoded->username;
	$content['college_id'] =  $this->token->decoded->college_id;
	$content['title'] = $body['title'];

	$content_type = $body['type'];
	$content['content_type_id'] = $content_type;

	$item = $this->spot->mapper("App\ContentType")
	->query("SELECT * FROM `content_types` 
	        WHERE `content_type_id` = ".$content_type)
	->first();

	$view_type_id = $item->default_view_type;
	$data["content_type"] = $content_type;
	$data["before"] = $view_type_id;

	$content['view_type'] = $view_type_id;
	$newContent = new Content($content);
	$mapper = $this->spot->mapper("App\Content");
	$mapper->save($newContent);

	$is_changed = false;

	for ($i=0; $i < count($body['items']); $i++) {

		$media_type = $body['items'][$i]['mediaType'];

		if ((bool)$item->has_multiple_view_types && !$is_changed) {
			if ($view_type_id == 1 && ($media_type == 'image' || $media_type == 'cover')){
				$view_type_id = 0;
				$is_changed = true;
			} elseif ($view_type_id == 5 && 
			          ($media_type == 'youtube' || $media_type == 'video' || $media_type == 'vimeo')){
				$view_type_id = 4;
				$is_changed = true;
			}
		}

		$items['content_id'] = $newContent->content_id;
		$items['description'] = isset($body['items'][$i]['text'])?$body['items'][$i]['text']:NULL;
		$items['content_item_type'] = $media_type;
		$items['priority'] = $i;
		$items['image'] = isset($body['items'][$i]['image'])?$body['items'][$i]['image']:NULL;
		$items['embed'] = isset($body['items'][$i]['embed'])?$body['items'][$i]['embed']:NULL;
		$items['embed_url'] = isset($body['items'][$i]['embedUrl'])?$body['items'][$i]['embedUrl']:NULL;
		$itemsElement = new ContentItems($items);
		$this->spot->mapper("App\ContentItems")->save($itemsElement);
	}

	for ($i=0; $i < count($body['tags']); $i++) {
		$tags['content_id'] = $data['data']['content_id'];
		$tags['name'] = $body['tags'][$i]['name'];
		$tagsElement = new ContentTags($tags);
		$this->spot->mapper("App\ContentTags")->save($tagsElement);
	}

	$done = false;

	if ($is_changed) {
		$data["after"] = $view_type_id;
		$newContent->view_type = $view_type_id;
		$done = $mapper->update($newContent);
	}

	/* Serialize the response data. */
	$data["isChanged"] = $is_changed;
	$data["id"] = $newContent->content_id;
	$data["message"] = 'Added Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/addNew", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$content['created_by_username'] =  $this->token->decoded->username;
	$content['college_id'] =  $this->token->decoded->college_id;
	$content['title'] = $body['title'];
	$content['content_type_id'] = $body['type'];
	
	$newContent = new Content($content);
	$this->spot->mapper("App\Content")->save($newContent);

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($newContent, new ContentTransformer);
	$data = $fractal->createData($resource)->toArray();
			//adding interests 

	for ($i=0; $i < count($body['items']); $i++) {
		$items['content_id'] = $data['data']['content_id'];
		$items['description'] = isset($body['items'][$i]['text'])?$body['items'][$i]['text']:"";
		$items['content_item_type'] = $body['items'][$i]['mediaType'];
		$items['image'] = isset($body['items'][$i]['image'])?$body['items'][$i]['image']:"";
		$items['embed_url'] = isset($body['items'][$i]['embedUrl'])?$body['items'][$i]['embedUrl']:"";
		$itemsElement = new ContentItems($items);
		$this->spot->mapper("App\ContentItems")->save($itemsElement);
	}
	for ($i=0; $i < count($body['tags']); $i++) {
		$tags['content_id'] = $data['data']['content_id'];
		$tags['name'] = $body['tags'][$i]['name'];
		$tagsElement = new ContentTags($tags);
		$this->spot->mapper("App\ContentTags")->save($tagsElement);
	}

	/* Serialize the response data. */
	$data["status"] = true;
	$data["message"] = 'Added Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch("/content/{id}", function ($request, $response, $arguments) {

	// /* Check if token has needed scope. */
	// if (true === $this->token->hasScope(["event.all", "event.update"])) {
	// 	throw new ForbiddenException("Token not allowed to update events.", 403);
	// }

	/* Load existing event using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["id"],])) {
		throw new NotFoundException("Content not found.", 404);
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
	$content->data($body);
	$this->spot->mapper("App\Content")->save($content);

// /* Add Last-Modified and ETag headers to response. */
// $response = $this->cache->withEtag($response, $event->etag());
// $response = $this->cache->withLastModified($response, $event->timestamp());

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($content, new ContentTransformer);
	$data = $fractal->createData($resource)->toArray();
	$data["status"] = "ok";
	$data["message"] = "Content updated";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete("/content/{id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 404);
	}

	/* Load existing content using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first([
	                                                                   "content_id" => $arguments["id"],
	                                                                   ])) {
		throw new NotFoundException("Content not found.", 404);};

	if ($content->created_by_username != $token->username) {
		throw new ForbiddenException("Only the owner can delete the content", 404);
	}

	$this->spot->mapper("App\Content")->delete($content);

	$data["status"] = "ok";
	$data["message"] = "Content deleted";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
