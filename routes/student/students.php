<?php

use App\Student;
use App\StudentSkill;
use App\studentFollow;
use App\EventTransformer;
use App\EventMiniTransformer;
use App\StudentTransformer;
use App\StudentMiniTransformer;
use App\StudentFollowTransformer;
use App\ContentTransformer;
use App\ContentMiniTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get("/searchStudent/{query}", function($request, $response, $arguments){
    $query = $arguments['query'];
    $student = $this->spot->mapper("App\Student")
    ->query("SELECT * FROM students
             WHERE username = '". $query ."'");
    if(count($student)>0){
        $data["status"] = true;
    }
    else{
        $data["status"] = false;
    }
    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/student", function ($request, $response, $arguments) {
    
    $transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    if($token){
        $test = $token->username;
    }
    else{
        $test = '0';
    }
    $username = isset($_GET['username']) ? $_GET['username'] : $test;

    /* Load existing student using provided id */
    if (false === $student = $this->spot->mapper("App\Student")->first([
        "username" => $username
        ])) {
        throw new NotFoundException("Student not found.", 404);
    };

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    if($transformer === "mini"){
        $resource = new Item($student, new StudentMiniTransformer(['username' => $test, 'type' => 'get']));
    }
    else{
        $resource = new Item($student, new StudentTransformer(['username' => $test, 'type' => 'get']));
    }
    $data = $fractal->createData($resource)->toArray();
    $data['meta']['transformer']= $transformer;

    return $response->withStatus(200)
    ->withHeader("Content-Type", "appliaction/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/studentEvents", function ($request, $response, $arguments) {

    $transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";
    $filters = isset($_GET['filters']) ? $_GET['filters'] : "default";
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 4;
    $offset = isset($_GET['offset']) ? $_GET['offset'] : 0; 
    //sort_by can takes price, time_created and many other flags
    $sort_by = isset($_GET['sortby']) ? $_GET['sortby'] : "time_created";
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : "DESC";
    $valid_sorty_by = ["from_time", "price"];

    $exists = in_array($sort_by,$valid_sorty_by);
    echo $exists;
    if (!$exists){
        $sort_by = "time_created";
    }

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    if($token){
        $test = $token->username;
    }
    else{
        $test = '0';
    }
    $username = isset($_GET['username']) ? $_GET['username'] : $test;

    /* Use ETag and date from Event with most recent update. */
    $first = $this->spot->mapper("App\Event")
    ->all()
    ->where(["created_by_username" => $username])
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
    $events = $this->spot->mapper("App\Event")
    ->all()
    ->where(["created_by_username" => $username])
    ->limit($limit, $offset)
    ->order([$sort_by => $sort_order]);

    $offset += $limit;

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    if (isset($_GET['include'])) {
        $fractal->parseIncludes($_GET['include']);
    }
   if($transformer === "mini"){
        $resource = new Collection($events, new EventMiniTransformer(['username' => $test]));
    }
    else{
        $resource = new Collection($events, new EventTransformer(['username' => $test]));
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

$app->get("/studentContents", function ($request, $response, $arguments) {

    $transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";
    $filters = isset($_GET['filters']) ? $_GET['filters'] : "default";
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 4;
    $offset = isset($_GET['offset']) ? $_GET['offset'] : 0; 
    //sort_by can takes price, time_created and many other flags
    $sort_by = isset($_GET['sortby']) ? $_GET['sortby'] : "timer";
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : "DESC";
    $valid_sorty_by = ["time", "price"];

    $exists = in_array($sort_by,$valid_sorty_by);
    echo $exists;
    if (!$exists){
        $sort_by = "timer";
    }

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    if($token){
        $test = $token->username;
    }
    else{
        $test = '0';
    }
    $username = isset($_GET['username']) ? $_GET['username'] : $test;


    /* Use ETag and date from Content with most recent update. */
    $first = $this->spot->mapper("App\Content")
    ->all()
    ->where(["created_by_username" => $username])
    ->order(["timer" => "DESC"])
    ->first();

    /* Add Last-Modified and ETag headers to response when atleast on content exists. */
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

    $contents = $this->spot->mapper("App\Content")
    ->all()
    ->where(["created_by_username" => $username])
    ->limit($limit, $offset)
    ->order([$sort_by => $sort_order]);

    $offset += $limit;

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    if (isset($_GET['include'])) {
        $fractal->parseIncludes($_GET['include']);
    }
    if($transformer === "mini"){
        $resource = new Collection($contents, new ContentMiniTransformer(['username' => $test]));
    }
    else{
        $resource = new Collection($contents, new ContentTransformer(['username' => $test]));
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

$app->patch("/student", function ($request, $response, $arguments) {

    $transformer = isset($_GET['transformer']) ? $_GET['transformer'] : "default";

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    if($token){
        $test = $token->username;
    }
    else{
        throw new ForbiddenException("Permission Denied", 403);
    }

    /* Load existing student using provided username */
    if (false === $student = $this->spot->mapper("App\Student")->first([
        "username" => $test
        ])) {
        throw new NotFoundException("Student not found.", 404);
    };

    $body = $request->getParsedBody();
    $student->data($body);
    $this->spot->mapper("App\Student")->save($student);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    if($transformer === "mini"){
       $resource = new Item($student, new StudentMiniTransformer);
    }
    else{
       $resource = new Item($student, new StudentTransformer);    
    }
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Student updated";
    $data["meta"]["transformer"]= $transformer;

    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/studentSkill", function ($request, $response, $arguments) {

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    if($token){
        $test = $token->username;
    }
    else{
        throw new ForbiddenException("Permission Denied", 403);
    }
    $skill_count = $this->spot->mapper("App\StudentSkill")->query("SELECT * FROM `student_skills` WHERE username = '". $test ."'");
    if(count($skill_count)>=5){
        throw new ForbiddenException("Can add only five skills", 403);
    }else{
    $body = $request->getParsedBody();
    $skill_name = $body['skill'];

    $newSkill['skill_name'] = $skill_name;
    $newSkill['username'] = $test;
    if($skills_existing = $this->spot->mapper("App\StudentSkill")->first(['skill_name' =>    $skill_name, 'username' => $test])){
        throw new ForbiddenException("Already Added.", 400);  
    }else{
    $addSkill = new StudentSkill($newSkill);
    $this->spot->mapper("App\StudentSkill")->save($addSkill);
    $data['status'] = "ok";
    $data['message'] = "Skill Added";
    /* Serialize the response data. */
    return $response->withStatus(201)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }}
});

$app->delete("/studentSkill/{id}", function ($request, $response, $arguments) {

    $token = $request->getHeader('authorization');
    $token = substr($token[0], strpos($token[0], " ") + 1); 
    $JWT = $this->get('JwtAuthentication');
    $token = $JWT->decodeToken($JWT->fetchToken($request));

    if($token){
        $test = $token->username;
    }
    else{
        throw new ForbiddenException("Permission Denied", 403);
    }

    if(false === $deleteSkill = $this->spot->mapper("App\StudentSkill")->first(["id" => $arguments["id"]])){
        throw new NotFoundException("Had never added it.", 404);
    }
    else{
    $id = $this->spot->mapper("App\StudentSkill")->delete($deleteSkill);
    }

    if($id){
        $data["status"] = "ok";
        $data["message"] = "Skill deleted successfully.";
        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
    else{
        $data["status"] = "error";
        $data["message"] = "Error deleting Skill!";
        return $response->withStatus(500)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->post("/studentFollow/{username}", function ($request, $response, $arguments) {
    if($arguments['username']!=$this->token->decoded->username){

        $participants = $this->spot->mapper("App\StudentFollow")->query("SELECT * FROM `followers` WHERE followed_username = '".  $arguments['username'] ."' AND follower_username = '" .$this->token->decoded->username. "'");

        if(count($participants) > 0){
            $data["status"] = "Already Following";
        } else {
            $event['followed_username'] =  $arguments['username'];
            $event['follower_username'] =  $this->token->decoded->username;

            $newEvent = new StudentFollow($event);
            $this->spot->mapper("App\StudentFollow")->save($newEvent);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($newEvent, new StudentFollowTransformer);
            $data = $fractal->createData($resource)->toArray();
        }

        /* Serialize the response data. */
        return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
    return $response->withStatus(201)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode("don't be that narsistic", JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete("/studentFollow/{username}", function ($request, $response, $arguments) {
    $body = $request->getParsedBody();

    /* Load existing todo using provided uid */
    $rsvp = $this->spot->mapper("App\StudentFollow")->query("SELECT * FROM `followers` WHERE followed_username = '". $arguments['username'] ."' AND follower_username = '" .$this->token->decoded->username. "'");
    if(count($rsvp) <= 0){
        $data["status"] = "Not Following";
    } else {
        $rsvp = $this->spot->mapper("App\StudentFollow")->query("SELECT * FROM `followers` WHERE followed_username = '". $arguments['username'] ."' AND follower_username = '" .$this->token->decoded->username. "'")->first();
        $this->spot->mapper("App\StudentFollow")->delete($rsvp);

        $data["status"] = "ok";
        $data["message"] = "Unfollowed";
    }

    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get("/userImage", function ($request, $response, $arguments) {

    $username =$this->token->decoded->username;

    $follows = $this->spot->mapper("App\Student")
        ->query("
                SELECT name, image
                FROM students
                WHERE username = '". $username ."' ");
        $data['username'] = $this->token->decoded->username;
        $data['name'] = $follows[0]->name;
        $data['image'] = $follows[0]->image;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
