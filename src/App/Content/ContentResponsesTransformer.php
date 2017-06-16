<?php

 

namespace App;

use App\ContentResponses;
use League\Fractal;

class ContentResponsesTransformer extends Fractal\TransformerAbstract {

	public function transform(ContentResponses $contentresponses) {
		return [
			"content_response_id" => (integer) $contentresponses->content_response_id ?: 0,
			"content_id" => (integer) $contentresponses->content_id ?: 0,
			"username" => (string) $contentresponses->username ?: null,
			"response_text" => (string) $contentresponses->response_text ?: null,

			/**
			 * This part has to be replaced with data from studentMiniTransformer
			 */
			"name" => "Ayush Pahwa",
			"image" => "https://scontent.xx.fbcdn.net/v/t1.0-1/13051612_1088563414516302_8913007315739796978_n.jpg?oh=588ab4000434e319175c17aab94ea0c1&oe=5950465C",
		];
	}
}
