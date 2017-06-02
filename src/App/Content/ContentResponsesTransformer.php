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
		];
	}
}
