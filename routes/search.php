<?php

use App\Event;
use App\EventTransformer;
use App\Content;
use App\ContentTransformer;
use App\ContentMiniTransformer;
use App\EventMiniTransformer;
use App\StudentSearchTransformer;
use App\StudentMiniTransformer;
use Exception\ForbiddenException;
use Exception\NotFoundException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

$app->get("/search/students/{query}", function ($request, $response, $arguments) {

  
  /* If-Modified-Since and If-None-Match request header handling. */
  /* Heads up! Apache removes previously set Last-Modified header */
  /* from 304 Not Modified responses. */
  if ($this->cache->isNotModified($request, $response)) {
    return $response->withStatus(304);
  }

  $students = $this->spot->mapper("App\Student")->query('
SELECT 
students.*, 
(CASE 
  WHEN (student_skills.skill_name LIKE "'.$arguments['query'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (student_skills.skill_name LIKE "%'.$arguments['query'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (name LIKE "%'.$arguments['query'].'%") 
  THEN 50 ELSE 0 END) AS score2, 
(CASE 
  WHEN (name LIKE "'.$arguments['query'].'%") 
  THEN 50 ELSE 0 END) AS score2, 
(CASE 
  WHEN (students.username LIKE "%'.$arguments['query'].'%") 
  THEN 150 ELSE 0 END) AS score1, 
(CASE 
  WHEN (students.username LIKE "'.$arguments['query'].'%") 
  THEN 150 ELSE 0 END) AS score1 
FROM students 
LEFT JOIN student_skills 
ON students.username = student_skills.username 
WHERE student_skills.skill_name LIKE "'.$arguments['query'].'%"
OR student_skills.skill_name LIKE "%'.$arguments['query'].'%"
OR name LIKE "%'.$arguments['query'].'%"
OR name LIKE "'.$arguments['query'].'%"
OR students.username LIKE "%'.$arguments['query'].'%"
OR students.username LIKE "'.$arguments['query'].'%"
GROUP BY students.username 
ORDER BY score1 DESC,score2 DESC,score3 DESC
                                                        ');

  if(isset($students) ){
    /* Serialize the response data. */
    $fractal = new Manager();

    $fractal->setSerializer(new DataArraySerializer);

    $resource = new Collection($students, new StudentSearchTransformer);
    $data = $fractal->createData($resource)->toArray();
  return $response->withStatus(200)
  ->withHeader("Content-Type", "appliaction/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  }
});

$app->get("/search/events/{title}", function ($request, $response, $arguments) {

  /* If-Modified-Since and If-None-Match request header handling. */
  /* Heads up! Apache removes previously set Last-Modified header */
  /* from 304 Not Modified responses. */
  if ($this->cache->isNotModified($request, $response)) {
    return $response->withStatus(304);
  }
  $test = isset($this->token->decoded->username)?$this->token->decoded->username:'0';
  $events = $this->spot->mapper("App\Event")->query('
SELECT 
*, 
(CASE 
  WHEN (event_tags.name LIKE "'.$arguments['title'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (event_tags.name LIKE "%'.$arguments['title'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (events.title LIKE "%'.$arguments['title'].'%") 
  THEN 150 ELSE 0 END) AS score1, 
(CASE 
  WHEN (events.title LIKE "'.$arguments['title'].'%") 
  THEN 150 ELSE 0 END) AS score1 
FROM events 
LEFT JOIN event_tags 
ON events.event_id = event_tags.event_id 
WHERE event_tags.name LIKE "'.$arguments['title'].'%"
OR event_tags.name LIKE "%'.$arguments['title'].'%"
OR events.title LIKE "%'.$arguments['title'].'%"
OR events.title LIKE "'.$arguments['title'].'%"
GROUP BY events.event_id 
ORDER BY score1 DESC,score3 DESC
                                                    ');

  if(isset($events) ){
    /* Serialize the response data. */
    $fractal = new Manager();

    $fractal->setSerializer(new DataArraySerializer);

    $resource = new Collection($events, new EventMiniTransformer(['username' => $test, 'type' => 'get']));
    $data = $fractal->createData($resource)->toArray();
  }
  return $response->withStatus(200)
  ->withHeader("Content-Type", "appliaction/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->get("/search/creativity/{username}", function ($request, $response, $arguments) {

  /* If-Modified-Since and If-None-Match request header handling. */
  /* Heads up! Apache removes previously set Last-Modified header */
  /* from 304 Not Modified responses. */
  if ($this->cache->isNotModified($request, $response)) {
    return $response->withStatus(304);
  }

  $creativity = $this->spot->mapper("App\Content")->query('
                                                          SELECT * FROM contents
                                                          WHERE title LIKE "%'.$arguments['username'].'%"
                                                          ');

  if(isset($creativity) ){
    /* Serialize the response data. */
    $fractal = new Manager();

    $fractal->setSerializer(new DataArraySerializer);

    $resource = new Collection($creativity, new ContentMiniTransformer);
    $data = $fractal->createData($resource)->toArray();
  }
  return $response->withStatus(200)
  ->withHeader("Content-Type", "appliaction/json")
  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->get("/search/{query}", function ($request, $response, $arguments) {

  $test = isset($this->token->decoded->username)?$this->token->decoded->username:'0';
  $query =isset($arguments["query"])?isset($arguments["query"]):" ";

  $students = $this->spot->mapper("App\Student")->query('
SELECT 
students.*, 
(CASE 
  WHEN (student_skills.skill_name LIKE "'.$arguments['query'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (student_skills.skill_name LIKE "%'.$arguments['query'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (name LIKE "%'.$arguments['query'].'%") 
  THEN 50 ELSE 0 END) AS score2, 
(CASE 
  WHEN (name LIKE "'.$arguments['query'].'%") 
  THEN 50 ELSE 0 END) AS score2, 
(CASE 
  WHEN (students.username LIKE "%'.$arguments['query'].'%") 
  THEN 150 ELSE 0 END) AS score1, 
(CASE 
  WHEN (students.username LIKE "'.$arguments['query'].'%") 
  THEN 150 ELSE 0 END) AS score1 
FROM students 
LEFT JOIN student_skills 
ON students.username = student_skills.username 
WHERE student_skills.skill_name LIKE "'.$arguments['query'].'%"
OR student_skills.skill_name LIKE "%'.$arguments['query'].'%"
OR name LIKE "%'.$arguments['query'].'%"
OR name LIKE "'.$arguments['query'].'%"
OR students.username LIKE "%'.$arguments['query'].'%"
OR students.username LIKE "'.$arguments['query'].'%"
GROUP BY students.username 
ORDER BY score1 DESC,score2 DESC,score3 DESC
                                                        ');
  $resourceStudents = new Collection($students, new StudentSearchTransformer);

  $creativity = $this->spot->mapper("App\Content")->query('
                                                          SELECT * FROM contents
                                                          WHERE title LIKE "'.$arguments['query'].'%"
                                                          OR title LIKE "% '.$arguments['query'].'%"

                                                          ');
  $resourceCreativity = new Collection($creativity, new ContentMiniTransformer);

  $events = $this->spot->mapper("App\Event")->query('
SELECT 
*, 
(CASE 
  WHEN (event_tags.name LIKE "'.$arguments['query'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (event_tags.name LIKE "%'.$arguments['query'].'%") 
  THEN 10 ELSE 0 END) AS score3, 
(CASE 
  WHEN (events.title LIKE "%'.$arguments['query'].'%") 
  THEN 150 ELSE 0 END) AS score1, 
(CASE 
  WHEN (events.title LIKE "'.$arguments['query'].'%") 
  THEN 150 ELSE 0 END) AS score1 
FROM events 
LEFT JOIN event_tags 
ON events.event_id = event_tags.event_id 
WHERE event_tags.name LIKE "'.$arguments['query'].'%"
OR event_tags.name LIKE "%'.$arguments['query'].'%"
OR events.title LIKE "%'.$arguments['query'].'%"
OR events.title LIKE "'.$arguments['query'].'%"
GROUP BY events.event_id 
ORDER BY score1 DESC,score3 DESC
                                                    ');
  $resourceEvents = new Collection($events, new EventMiniTransformer(['username' => $test, 'type' => 'get']));

  $data = NULL;
  /* Serialize the response data. */
  $fractal = new Manager();
  $fractal->setSerializer(new DataArraySerializer);
  if (isset($_GET['include'])) 
    $fractal->parseIncludes($_GET['include']);

  if(isset($resourceStudents) ){
    $arr = $fractal->createData($resourceStudents)->toArray();
    $data['students'] = $arr;
  }

  if(isset($creativity) ){
    $arr = $fractal->createData($resourceCreativity)->toArray();
    $data['content'] = $arr;
  }
  
  if(isset($events) ){
    $arr = $fractal->createData($resourceEvents)->toArray();
    $data['event'] = $arr;
  }

    return $response->withStatus(200)
    ->withHeader("Content-Type", "application/json")
    ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
