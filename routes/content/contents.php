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
		throw new ForbiddenException("Token not found", 401);
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

		WHERE contents.status LIKE 'active'

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
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>"active"])
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->where(["status"=>"active"])
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
		->where(["content_type_id"=>$filters, "status"=>"active"])
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>"active"])
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
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>"active"])
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>"active"])
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
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>"active"])
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>"active"])
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
	->query("SELECT * from contents WHERE status LIKE 'active'  ORDER BY RAND() limit 3"); 

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
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>"active"])
		->order(["timer" => "DESC"])
		->first();

	}else{

		$first = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>"active"])
		->order(["timer" => "DESC"])
		->first();
	}

	if(isset($arguments['content_type_id'])){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>"active"])
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>"active"])
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
		"content_id" => $arguments["id"], "status" => "active",
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
	if(isset($token->college_id)){
		if($token->college_id==NULL){
			$college_id = 0;
		}
	}else{
		$college_id = $token->college_id;
	}


	$content['created_by_username'] =  $this->token->decoded->username;
	$content['college_id'] =  $college_id;
	$content['title'] = $body['title'];

	$content_type = $body['type'];
	$content['content_type_id'] = $content_type;

	$content['status'] = 'active';

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

	/* Load existing content using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["id"],"status" => "active",])) {
		throw new NotFoundException("Content not found.", 404);
	};

	$body = $request->getParsedBody();
	$content->data($body);
	$this->spot->mapper("App\Content")->save($content);

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

$app->delete("/delContent/{content_id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 401);
	}

	/* Load existing content using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first([
		"content_id" => $arguments["content_id"],
		])) {
		throw new NotFoundException("Content not found.", 404);};

	if ($content->created_by_username != $token->username) {
		throw new ForbiddenException("Only the owner can delete the content", 404);
	}

	if ($content->status === "inactive") {
		throw new ForbiddenException("Content already removed", 404);
	}

	$update_status = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["content_id"]]);

	if ($update_status) {
		$update_status->status = "inactive";
		$this->spot->mapper("App\Content")->update($update_status);
	}
	$data["status"] = "ok";
	$data["message"] = "Content removed";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/newDraftContent", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$content['created_by_username'] =  $this->token->decoded->username;
	$content['college_id'] =  $this->token->decoded->college_id;
	$content['title'] = $body['title'];
	$content['content_type_id'] = $body['type'];
	
	$content['status'] = "draft";

	$newContent = new Content($content);
	$this->spot->mapper("App\Content")->save($newContent);

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($newContent, new ContentTransformer);
	$data = $fractal->createData($resource)->toArray();

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
	$data["message"] = 'Saved to draft Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete("/movetoDraftContent/{content_id}", function ($request, $response, $arguments) {

	$token = $request->getHeader('authorization');
	$token = substr($token[0], strpos($token[0], " ") + 1); 
	$JWT = $this->get('JwtAuthentication');
	$token = $JWT->decodeToken($JWT->fetchToken($request));

	if (!$token) {
		throw new ForbiddenException("Token not found", 401);
	}

	/* Load existing content using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first([
		"content_id" => $arguments["content_id"],
		])) {
		throw new NotFoundException("Content not found.", 404);};

	if ($content->created_by_username != $token->username) {
		throw new ForbiddenException("Only the owner can move the content to draft", 404);
	}

	if ($content->status === "draft") {
		throw new ForbiddenException("Content already moved to draft", 404);
	}

	$update_status = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["content_id"]]);

	if ($update_status) {
		$update_status->status = "draft";
		$this->spot->mapper("App\Content")->update($update_status);
	}
	$data["status"] = "ok";
	$data["message"] = "Content successfully moved to draft.";

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/editDraftContent/{content_id}", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();
	
	$update_content = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["content_id"]]);
	if ($update_content) {

	$update_content->created_by_username =  $this->token->decoded->username;
	$update_content->college_id =  $this->token->decoded->college_id;
	$update_content->title = $body['title'];
	$update_content->content_type_id = $body['type'];

	$update_content->status = "active";

	$this->spot->mapper("App\Content")->update($update_content);
	}

	$fractal = new Manager();
	$fractal->setSerializer(new DataArraySerializer);
	$resource = new Item($update_Content, new ContentTransformer);
	$data = $fractal->createData($resource)->toArray();


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
	$data["status"] = true ;
	$data["message"] = 'Saved Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
