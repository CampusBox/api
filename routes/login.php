<?php
/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */
use App\Student;
use Facebook\Facebook;
use Firebase\JWT\JWT;
use Tuupola\Base62;

$app->post("/login", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();

	$student = new Student($body);
	$student = $this->spot
		->mapper("App\Student")
		->where(['username' => $body["username"], 'password' => $body["password"]]);

	if (count($student) == 0) {
		return $response->withStatus(201)
			->withHeader("Content-Type", "application/json")
			->write(json_encode("error", JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}
	$now = new DateTime();
	$future = new DateTime("now +30 days");
	$server = $request->getServerParams();
	$jti = Base62::encode(random_bytes(16));
	$payload = [
		"iat" => $now->getTimeStamp(),
		"exp" => $future->getTimeStamp(),
		"jti" => $jti,
		"student_id" => $student[0]->student_id,
	];
	$secret = getenv("JWT_SECRET");
	$token = JWT::encode($payload, $secret, "HS256");
	$data["status"] = "ok";
	$data["token"] = $token;

	return $response->withStatus(201)
		->withHeader("Content-Type", "application/json")
		->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post("/facebook", function ($request, $response, $arguments) {
	$body = $request->getParsedBody();
	$fb = new \Facebook\Facebook([
		'app_id' => '1250377088376164',
		'app_secret' => '9ea27671762a7c1b1899f5b10c45f950',
		'default_graph_version' => 'v2.8',
	]);
	try {
		$x = $fb->get('/me?fields=email,name,id', $body['access_token']);
	} catch (\Facebook\Exceptions\FacebookResponseExpception $e) {
		echo 'Graph returned an error: ' . $e->getMessage();
		exit;
	} catch (\Facebook\Exceptions\FacebookSDKException $e) {
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}
	$me = $x->getGraphUser();
	$student = new Student();
	$student = $this->spot
		->mapper("App\Student")
		->where(['email' => $me['email']]);

	if (count($student) == 0) {
		$data["registered"] = false;
		$data["name"] = $me['name'];
		$data["email"] = $me['email'];

		return $response->withStatus(201)
			->withHeader("Content-Type", "application/json")
			->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}
	$now = new DateTime();
	$future = new DateTime("now +30 days");
	$server = $request->getServerParams();
	$jti = Base62::encode(random_bytes(16));
	$payload = [
		"iat" => $now->getTimeStamp(),
		"exp" => $future->getTimeStamp(),
		"jti" => $jti,
		"student_id" => $student[0]->student_id,
	];
	$secret = getenv("JWT_SECRET");
	$token = JWT::encode($payload, $secret, "HS256");
	$data["status"] = "ok";
	$data["registered"] = true;
	$data["token"] = $token;

	return $response->withStatus(201)
		->withHeader("Content-Type", "application/json")
		->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
