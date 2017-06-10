<?php

namespace App;
use Spot\EntityInterface as Entity;
use Spot\EventEmitter;
use Spot\MapperInterface as Mapper;
use Tuupola\Base62;

class ReportEvent extends \Spot\Entity {
	protected static $table = "report_event";
	public static function fields() {
		return [
		"report_event_id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
		"event_id" => ["type" => "integer", "unsigned" => true],
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