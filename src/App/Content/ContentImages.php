<?php

 

namespace App;

use Spot\Entity ;
use Spot\EntityInterface ;
use Spot\EventEmitter;
use Spot\MapperInterface;
use Tuupola\Base62;

class ContentImages extends \Spot\Entity {
	protected static $table = "content_images";

	public static function fields() {
		return [

			"id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
			"content_item_id" => ["type" => "integer", "unsigned" => true],
			"data" => ["type" => "string"],
			"timer" => ["type" => "datetime"]
		];
	}

	public static function contents(EventEmitter $emitter) {
		$emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
			$entity->timer = new \DateTime();
		});

		$emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
			$entity->timer = new \DateTime();
		});
	}
	public function timestamp() {
		$abc =  new \DateTime();
		return $abc->getTimestamp();
	}

	public function etag() {
		return md5($this->content_id . $this->timestamp());
	}
 
	public function clear() {
		$this->data([
		]);
	}

	public static function relations(MapperInterface $mapper, EntityInterface $entity) {
		return [
			'ContentItem' => $mapper->belongsTo($entity, 'App\ContentItems', 'content_item_id'),
		];
	}
}
