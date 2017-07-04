<?php

use App\Content;
use App\ContentItems;
use App\ContentImages;
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

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

/**
 * Get creative contents 'limit' no of times
 * Structure: /contents?limit=3&offset=0
 * *NOTE* Not going to be used in the future. The post API will be used instead.
 */
$app->get("/contents", function ($request, $response, $arguments) {

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

	$contents = $this->spot->mapper("App\Content")
	->where(["status"=>0])
	->limit($limit, $offset)
	->order(["timer" => "DESC"]);
	
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

/**
 * Gets 'limit' no of contents after 'offset' number of elements filtered by 'filters'
 * Arguments:	limit (optional) - number of contents to get
 * 				offset (optional) - index till which last data is already present in backend
 * 				filters (optional) - Array of content type ids to be filtered against
 */
$app->post("/contents", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$limit = isset($body['limit']) ? $body['limit'] : 3;
	$offset = isset($body['offset']) ? $body['offset'] : 0;
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
		->where(["content_type_id"=>$filters, "status"=>0])
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>0])
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

	        WHERE contents.status = 0

	        GROUP BY contents.content_id
	        ORDER BY interestScore+interScore+followScore DESC,contents.timer 
	        LIMIT 3 OFFSET 0
	        ;");

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>0])
		->order(["randomint" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>0])
		->limit(6)
		->order(["randomint" => "DESC"]);
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

$app->post("/contentsList", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$limit = isset($body['limit']) ? $body['limit'] : 3;
	$offset = isset($body['offset']) ? $body['offset'] : 0;
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
		->where(["content_type_id"=>$filters, "status"=>0])
		->limit($limit, $offset)
		->order(["timer" => "DESC"]);
	}else{
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>0])
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

	$offset+=$limit;

	$data['meta']['offset'] = $offset;
	$data['meta']['limit'] = $limit;

	return $response->withStatus(200)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/contentsImage/{content_image_id}", function ($request, $response, $arguments) {

	$content = $this->spot->mapper("App\ContentImages")
	->where(["id"=>$arguments['content_image_id']])
	->first();

	if (false === $content) {
		throw new NotFoundException("Image not found.", 404);
	};

	$new_data=explode(";",$content->data);
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
	->query("SELECT * from contents WHERE status = 0  ORDER BY RAND() limit 3"); 

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
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>0])
		->order(["randomint" => "DESC"])
		->first();

	}else{

		$first = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>0])
		->order(["randomint" => "DESC"])
		->first();
	}

	if(isset($arguments['content_type_id'])){
		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["content_type_id"=>$arguments['content_type_id'], "status"=>0])
		->order(["randomint" => "DESC"]);
	}else{

		$contents = $this->spot->mapper("App\Content")
		->all()
		->where(["status"=>0])
		->order(["randomint" => "DESC"]);
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
	                                                                   "content_id" => $arguments["id"], "status" => 0,
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

	$content['status'] = 0;

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
	$wasAdded = $mapper->save($newContent);
	$url = "http://192.178.7.141/public/contentsImage/";

	if ($wasAdded) {	

		$is_changed = false;
		$proceed = true;

		for ($i=0; $i < count($body['items']); $i++) {

			$media_type = $body['items'][$i]['mediaType'];
			$items['content_item_type'] = $media_type;
			$updateImage = false;
			$updateThumb = false;

			/**
			 * Use when there is a content type that has multiple view_type
			 */
			// if ((bool)$item->has_multiple_view_types && !$is_changed) {
			// 	$provider = $body['items'][$i]['provider'];

			// 	if (($provider == 'youtube' || $provider == 'vimeo') && $view_type_id == 1){
			// 		$view_type_id = 2;
			// 		$is_changed = true;
			// 	}elseif (($view_type_id == 2) && ($provider!= 'youtube' || $provider!= 'vimeo' || $provider!= 'text')){
			// 		$view_type_id = 1;
			// 		$is_changed = true;
			// 	}
			// }

			if ($media_type == 'text') {
				if (isset($body['items'][$i]['text']) && ($body['items'][$i]['text'] != '')) {
					$items['data'] = $body['items'][$i]['text'];
					$items['host'] = "user";
				} else{
					continue;
				}
			} elseif ($media_type == 'image') {

				$inputImg = isset($body['items'][$i]['image'])?$body['items'][$i]['image']:NULL;
				$img['data'] = $inputImg;
				$items['data'] = "<img src=\"".$url."\">";
				$updateImage = true;
				$items['content_item_type'] = "embed";
				$items['host'] = "user";

				// $img['filters'] = isset($body['items'][$i]['filter'])?$body['items'][$i]['filter']:NULL;
			} elseif ($media_type == 'embed') {
				$items['data'] = isset($body['items'][$i]['iframe'])?$body['items'][$i]['iframe']:NULL;
				$items['thumbnail'] = isset($body['items'][$i]['thumbnailUrl'])?$body['items'][$i]['thumbnailUrl']:NULL;
				$items['host'] = isset($body['items'][$i]['provider'])?$body['items'][$i]['provider']:NULL;
				$items['url'] = isset($body['items'][$i]['url'])?$body['items'][$i]['url']:NULL;
				$items['author'] = isset($body['items'][$i]['author'])?$body['items'][$i]['author']:NULL;
			} elseif ($media_type == 'tech') {
				$items['data'] = isset($body['items'][$i]['url'])?$body['items'][$i]['url']:NULL;

				$thumb = isset($body['items'][$i]['icon'])?$body['items'][$i]['icon']:NULL;
				if (startsWith($thumb, "data:image/")) {
					$items['thumbnail'] = NULL;
					$img['data'] = $thumb;
					$updateThumb = true;
				} else{
					$items['thumbnail'] = $thumb;
				}

				$items['host'] = isset($body['items'][$i]['provider'])?$body['items'][$i]['provider']:NULL;
				$items['author'] = isset($body['items'][$i]['author'])?$body['items'][$i]['author']:NULL;
			} elseif ($media_type == 'sourceCodeUrl') {
				$items['data'] = isset($body['items'][$i]['url'])?$body['items'][$i]['url']:NULL;
				$items['thumbnail'] = isset($body['items'][$i]['icon'])?$body['items'][$i]['icon']:NULL;
				$items['host'] = isset($body['items'][$i]['provider'])?$body['items'][$i]['provider']:NULL;
				$items['author'] = isset($body['items'][$i]['author'])?$body['items'][$i]['author']:NULL;
			}

			$items['content_id'] = $newContent->content_id;
			$items['priority'] = $i;
			$itemsElement = new ContentItems($items);
			$wasAdded = $this->spot->mapper("App\ContentItems")->save($itemsElement);

			if ($wasAdded) {
				if($updateImage || $updateThumb){
					$img['content_item_id'] = $wasAdded;
					$newImage = new ContentImages($img);
					$mapper = $this->spot->mapper("App\ContentImages");
					$wasAdded = $mapper->save($newImage);
					if ($wasAdded) {
						$imgNew = "<img src=\"".$url.$wasAdded."\">";
						if ($updateImage) {
							$itemsElement->data = $imgNew;
						}
						$itemsElement->thumbnail = $url.$wasAdded;
						$done = $this->spot->mapper("App\ContentItems")->update($itemsElement);
					} else{
						throw new Exception("Image not added", 404);

					}
				} 
			}else{
				throw new ForbiddenException("Content item not added", 401);
			}

		}

		for ($i=0; $i < count($body['tags']); $i++) {
			$tags['content_id'] = $data['data']['content_id'];
			$tags['name'] = $body['tags'][$i]['name'];
			$tagsElement = new ContentTags($tags);
			$this->spot->mapper("App\ContentTags")->save($tagsElement);
		}

		$done = false;

		if ($proceed) {
			if ($is_changed) {
				$newContent->view_type = $view_type_id;
				$done = $mapper->update($newContent);
			}
		} else {
			$mapper->delete($newContent);
			throw new ForbiddenException("Content not supported for the given type", 401);
		}

		/* Serialize the response data. */
		$data["id"] = $newContent->content_id;
		$data["message"] = 'Added Successfully';
		return $response->withStatus(201)
		->withHeader("Content-Type", "application/json")
		->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	} else{
		$data["error"] = 'error';
		$data["message"] = 'Added Successfully';
		return $response->withStatus(500)
		->withHeader("Content-Type", "application/json")
		->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}
});

$app->post("/addNew", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();
	if(!$this->token->decoded->college_id){
		$this->token->decoded->college_id=0;
	}

	$content['status'] =  0;
	$content['view_type'] =  1;
	$content['created_by_username'] =  $this->token->decoded->username;
	$content['college_id'] =  $this->token->decoded->college_id;
	$content['title'] = $body['title'];
	$content['content_type_id'] = $body['type'];
	
	$newContent = new Content($content);
	$data['newContent'] = $newContent;
	$wasAdded = $this->spot->mapper("App\Content")->save($newContent);
	if($wasAdded){

		$fractal = new Manager();
		$fractal->setSerializer(new DataArraySerializer);
		$resource = new Item($newContent, new ContentTransformer);
		$data = $fractal->createData($resource)->toArray();
			//adding interests 

		for ($i=0; $i < count($body['items']); $i++) {
			$items['content_id'] = $newContent->content_id;
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
	}

	/* Serialize the response data. */
	$data["status"] = $wasAdded;
	$data["message"] = 'Added Successfully';
	return $response->withStatus(201)
	->withHeader("Content-Type", "application/json")
	->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch("/content/{id}", function ($request, $response, $arguments) {

	/* Load existing content using provided id */
	if (false === $content = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["id"],"status" => 0,])) {
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

$app->delete("/content/{content_id}", function ($request, $response, $arguments) {

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

	if ($content->status === 1) {
		throw new ForbiddenException("Content already removed", 404);
	}

	$update_status = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["content_id"]]);

	if ($update_status) {
		$update_status->status = 1;
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
	
	$content['status'] = 2;

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

	if ($content->status === 2) {
		throw new ForbiddenException("Content already moved to draft", 404);
	}

	$update_status = $this->spot->mapper("App\Content")->first(["content_id" => $arguments["content_id"]]);

	if ($update_status) {
		$update_status->status = 2;
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

		$update_content->status = 1;

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
