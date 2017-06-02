<?php

 

namespace App;

use Spot\Entity;
use Spot\EntityInterface;
use Spot\EventEmitter;
use Spot\MapperInterface;
use Tuupola\Base62;

class ContentResponses extends \Spot\Entity {
	protected static $table = "content_responses";

	public static function fields() {
		return [

			"content_response_id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
			"content_id" => ["type" => "integer", "unsigned" => true],
			"username" => ["type" => "string", "required" => true],
			"response_text" => ["type" => "string", "required" => true],
			"timed" => ["type" => "datetime"],
		];
	}

	public static function contents(EventEmitter $emitter) {
		$emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
			$entity->content_id = Base62::encode(random_bytes(9));
		});

		$emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
			$entity->time_created = new \DateTime();
		});
	}
	public function timestamp() {
		return $this->time_created->getTimestamp();
	}

	public function etag() {
		return md5($this->content_response_id . $this->timestamp());
	}

	public function clear() {
		$this->data([
		]);
	}

	public static function relations(Mapper $mapper, Entity $entity) {
		return [
			'Content' => $mapper->belongsTo($entity, 'App\Content', 'content_id'),
			'Student' => $mapper->belongsTo($entity, 'App\Student', 'username'),
		];
	}
}


