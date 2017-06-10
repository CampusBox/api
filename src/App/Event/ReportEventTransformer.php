<?php

namespace App;

use App\ReportEvent;
use League\Fractal;

class ReportEventTransformer extends Fractal\TransformerAbstract
{
    public function transform(ReportEvent $x)
    {
        return [
			"report_event_id" => (int)$x->report_event_id,
			"event_id" => (string)$x->event_id,
			"username" => (string)$x->username,
			"remarks" => (string)$x->remarks,
			"time_reported" => (string)$x->time_reported
        ];
    }
}
