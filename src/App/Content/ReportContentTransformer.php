<?php

namespace App;

use App\ReportEvent;
use League\Fractal;

class ReportContentTransformer extends Fractal\TransformerAbstract
{
    public function transform(ReportContent $x)
    {
        return [
			"report_content_id" => (int)$x->report_content_id,
			"content_id" => (string)$x->content_id,
			"username" => (string)$x->username,
			"remarks" => (string)$x->remarks,
			"time_reported" => (string)$x->time_reported
        ];
    }
}
