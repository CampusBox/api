<?php



namespace App;

use App\ContentItems;
use League\Fractal;

class ContentItemsTransformer extends Fractal\TransformerAbstract {

	public function transform(ContentItems $content_items) {
		if ($content_items->content_item_type == 'text') {
			return [
			"id" => (integer) $content_items->content_item_id ?: 0,
			"type" => (string) $content_items->content_item_type ?: 4,
			"priority" => (string) $content_items->priority ?: 0,
			"data" => (string) $content_items->data ?: null	
			];
		} elseif ($content_items->content_item_type == 'embed'){
			return [
			"id" => (integer) $content_items->content_item_id ?: 0,
			"type" => (string) $content_items->content_item_type ?: 4,
			"priority" => (integer) $content_items->priority ?: 0,
			"data" => (string) $content_items->data ?: null,
			"thumbnail" => (string) $content_items->thumbnail ?: null
			];
		} elseif ($content_items->content_item_type == 'tech'){
			return [
			"id" => (integer) $content_items->content_item_id ?: 0,
			"type" => (string) $content_items->content_item_type ?: 4,
			"priority" => (integer) $content_items->priority ?: 0,
			"data" => (string) $content_items->data ?: null,
			"icon" => (string) $content_items->thumbnail ?: null
			];
		} elseif ($content_items->content_item_type == 'sourceCodeUrl'){
			return [
			"id" => (integer) $content_items->content_item_id ?: 0,
			"type" => (string) $content_items->content_item_type ?: 4,
			"priority" => (integer) $content_items->priority ?: 0,
			"data" => (string) $content_items->data ?: null
			];
		}
		
	}
}
