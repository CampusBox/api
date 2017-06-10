<?php

namespace App;
use Spot\EntityInterface as Entity;
use Spot\EventEmitter;
use Spot\MapperInterface as Mapper;
use Tuupola\Base62;

class ReportContent extends \Spot\Entity {
	protected static $table = "report_content";
	public static function fields() {
		return [
		"report_content_id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
		"content_id" => ["type" => "integer", "unsigned" => true],
		"username" => ["type" => "string", "required" => true],
		"remarks" => ["type" => "string"],
		"time_reported" => ["type" => "string"],
		];
	}
	public static function relations(Mapper $mapper, Entity $entity) {
		return [
		];
	}
}