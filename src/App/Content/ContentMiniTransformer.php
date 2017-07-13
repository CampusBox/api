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
        $temp = null;
        if ($view_type == 1) {
            $items = $content->Items
            ->where(["content_item_type"=>"embed"]);
            $tempData = '';
            if(isset($items[0])){
            $data = $items[0]->data;
            if ($data!=null) {
                $tempData = $items[0]->data;
                $temp = array(
                          'embed' => $tempData
                          );
            } else{
                $tempData = $items[0]->thumbnail;
                $temp = array(
                          'thumbnail' => $tempData
                          );
            }  } 
        } elseif ($view_type == 2) {
            $items = $content->Items
            ->where(["content_item_type"=>"embed"]);
            $tempData = ''; 
            $overlay = '0'; 
            if(isset($items[0])){
            $tempData = $items[0]->thumbnail;
            if (in_array($content->content_type_id, [3,8,13,14])){
                $overlay = "video";
            } elseif (in_array($content->content_type_id, [9,10,11])){
                $overlay = "singing";
            }
            $temp = array(
                          'thumbnail' => $tempData,
                          'overlay' => $overlay,
                          );   }
        } elseif ($view_type == 3) {
            $items = $content->Items
            ->where(["content_item_type"=>"text"]);
            if(isset($items[0]))
            $temp = array(
                          'text' => $items[0]->data
                          );
        } elseif ($view_type == 4) {
            $items = $content->Items;
            $icon = '';
            $text = '';
            foreach ($items as $item) {
                if ($item->content_item_type == 'tech') {
                    $icon = $item->thumbnail;
                    $url = $item->data;
                } elseif ($item->content_item_type == 'text') {
                    $text = $item->data;
                }
            }
            $temp = array(
                          'icon' => $icon,
                          'text' => $text
                          );
        }
        $this->params['data'] = $temp;

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