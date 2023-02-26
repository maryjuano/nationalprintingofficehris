<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use \App\Notification;
use DateTime;
use DateInterval;
use DatePeriod;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class NotificationController extends Controller
{
    public function list(Request $request)
    {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }
        $query = \App\Notification::where('user_id', $this->me->id)->where('status', \App\Notification::NOTIFICATION_STATUS_UNREAD);
        $ALLOWED_FILTERS = ['status', 'source', 'section'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        $unread = $response['total'];

        $query = \App\Notification::where('user_id', $this->me->id);
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        $response['unread'] = $unread;
        return response()->json($response);
    }

    public function read(Request $request, \App\Notification $notification)
    {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }
        $notification->status = \App\Notification::NOTIFICATION_STATUS_READ;
        $notification->save();
        return response()->json($notification);
    }

}
