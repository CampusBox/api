<?php
namespace App;
use App\Content;
use League\Fractal;

class ContentMiniTransformer extends Fractal\TransformerAbstract {

    private $params = [];

    function __construct($params = []) {
        $this->params = $params;
        $this->params['value'] = false;
    }

    // protected $defaultIncludes = [
    // 'items'
    // ];

    public function transform(Content $content) {

        $appreciates = null;
        $bookmarks = null;
        $this->params['appreciateValue'] = 0;
        $this->params['bookmarkValue'] = 0;
        $this->params['data'] = null;
        if(isset($this->params['type']) && $this->params['type'] == 'get'){
            $appreciates = $content->Appreciated;
            for ($i=0; $i < count($appreciates); $i++) { 
                if($appreciates[$i]->username == $this->params['username']){
                    $this->params['appreciateValue'] = true;
                    break;
                }
            }
            $bookmarks = $content->Bookmarked;
            for ($i=0; $i < count($bookmarks); $i++) { 
                if($bookmarks[$i]->username == $this->params['username']){
                    $this->params['bookmarkValue'] = true;
                    break;
                }
            }
        }

        $view_type = $content->view_type;
        if ($view_type == 1) {
            $items = $content->Items
            ->where(["content_item_type"=>"embed"]);
            $data = $items[0]->data;
            if ($data!=null) {
                $this->params['data']->embed = $items[0]->data;
            } else{
                $this->params['data']->thumbnail = $items[0]->thumbnail;
            }      
        } elseif ($view_type == 2) {
            $items = $content->Items
            ->where(["content_item_type"=>"embed"]);
            $this->params['data']->thumbnail = $items[0]->thumbnail;
            if (in_array($content->content_type_id, [3,8,13,14])){
                $this->params['data']->overlay = "video";
            } elseif (in_array($content->content_type_id, [9,10,11])){
                $this->params['data']->overlay = "singing";
            }
        } elseif ($view_type == 3) {
            $items = $content->Items
            ->where(["content_item_type"=>"text"]);
            $this->params['data']->text = $items[0]->data;
        } elseif ($view_type == 4) {
            $items = $content->Items;
            foreach ($items as $item) {
                if ($item->content_item_type == 'text') {
                    continue;
                } elseif ($item->content_item_type == 'embed') {
                    $this->params['data']->url = $item->data;
                    $this->params['data']->icon = $item->thumbnail;
                } elseif ($item->content_item_type == 'sourceCodeUrl') {
                    $this->params['data']->sourceCodeUrl = $item->data;
                }
            }
        }

        return [
        "id" => (integer) $content->content_id ?: 0,
        "title" => (string) $content->title ?: null,
        "view_type" => (integer) $content->view_type ?: null,
        "content_type" => $content->content_type_id ?: 0,              
        "created_at" => $content->timer ?: 0,
        "items" => $this->params['data'],
        "owner" => [
        "username" => (string) $content->Owner['username'] ?: null,
        "name" => (string) $content->Owner['name'] ?: null,
        "about" => (string) $content->Owner['about'] ?: null,
        "photo" => (string) $content->Owner['image'] ?: null,
        ],
        "actions" => [
        "appreciate" => [
        "status" => (bool) $this->params['appreciateValue'] ?: false,
        "total" => (integer) count($appreciates) ?: 0,
        ],
        "bookmarks" => [
        "status" => (bool) $this->params['bookmarkValue'] ?: false,
        "total" =>  count($bookmarks) ?: 0,
        ],
        ],
        ];
    }
    // public function includeitems(Content $content) {
    //     $view_type = $content->view_type;
    //     if ($view_type) {
    //     $items = $content->Items->where();
    //     }

    //     return $this->collection($items, new ContentItemsTransformer);
    // }
}