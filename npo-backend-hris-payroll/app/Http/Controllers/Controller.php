<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

use App\Constants;
use JWTAuth;
use DateTime;
use Tymon\JWTAuth\Exceptions\JWTException;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  function __construct()
  {
    $this->DISABLE_AUTH = false;
  }

  public function is_not_authorized($authorization_needed = [])
  {
    $this->me = JWTAuth::parseToken()->authenticate();
    if (
      $this->DISABLE_AUTH ||
      (isset($this->me->permissions->hris) && in_array('admin', $this->me->permissions->hris)) ||
      count($authorization_needed) === 0
    ) {
      return false;
    }

    $total_authorization_needed = count($authorization_needed);
    $total_authorized = count(array_intersect($this->me->employee_details->system_information->privileges[0], $authorization_needed));
    if ($total_authorization_needed !== $total_authorized) {
      return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
    }

    return false;
  }

  public function paginate($request, $query)
  {
    $q = $request->input('q', null);

    $total = count(\DB::select($query->toSql(), $query->getBindings())); //$query->count();
    $page = (int)request('page', 1);
    $take = (int)request('take', $total);
    $skip = ($page - 1) * $take;
    $query = $query->skip($skip)->take($take);

    $sort_key = $request->input('sort_key', 'id');
    $sort_dir = $request->input('sort_dir', 'ascend') === 'ascend' ? 'asc' : 'desc';
    $data = $query->orderBy($sort_key, $sort_dir)->get();

    $filters = $request->all();
    unset($filters['skip']);
    unset($filters['page']);
    unset($filters['take']);
    unset($filters['total']);
    unset($filters['sort_dir']);
    unset($filters['sort_key']);
    unset($filters['q']);

    return array(
      'filters' => (object) $filters,
      'sort_dir' => $sort_dir === 'desc' ? 'descend' : 'ascend',
      'sort_key' => $sort_key,
      'page' => $page,
      'take' => $take,
      'total' => $total,
      'q' => $q,
      'data' => $data,
    );
  }

  public function paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS = [], $BOOL_FIELDS = [], $SEARCH_FIELDS = [], $IDS_COLUMN = "", $USE_OR_WHERE = false)
  {
    $q = request('q', null);
    if (request('q')) {
      $filter_queries = [];
      foreach ($SEARCH_FIELDS as $search_field) {
        $search_field = implode('`.`', explode('.', $search_field));
        array_push($filter_queries, "`$search_field` LIKE '%$q%' ");
      }
      if (sizeof($filter_queries) > 0) {
        $all_query = '(' . implode(' or ', $filter_queries) . ')';
        $query = $query->whereRaw($all_query);
      }
    }

    $all_filter_queries = [];
    $filters = [];

    foreach ($ALLOWED_FILTERS as $allowed_filter) {
      $filters[$allowed_filter] = request($allowed_filter);
      if (request($allowed_filter, null)) {
        $filter_queries = [];
        foreach (request($allowed_filter) as $filter) {
          array_push($filter_queries, "`$allowed_filter` = '$filter' ");
        }
        if (sizeof($filter_queries) > 0) {
          $filter_query = '(' . implode(' or ', $filter_queries) . ')';
          array_push($all_filter_queries, $filter_query);
        }
      }
    }
    if (sizeof($all_filter_queries) > 0) {
      $all_query = '(' . implode(' and ', $all_filter_queries) . ')';
      if ($USE_OR_WHERE) {
        $query = $query->orWhereRaw($all_query);
      } else {
        $query = $query->whereRaw($all_query);
      }
    }

    $ids = [];
    if ($IDS_COLUMN != "") {
      $datas = \DB::select($query->toSql(), $query->getBindings());
      $ids = collect($datas)->map(function ($item) use ($IDS_COLUMN) {
        return $item->{$IDS_COLUMN};
      });
    }

    // pagination
    $total = count(\DB::select($query->toSql(), $query->getBindings())); //$query->count();
    $page = (int)request('page', 1);
    $take = (int)request('take', $total);
    $skip = ($page - 1) * $take;
    $query = $query->skip($skip)->take($take);

    // sorting
    $sort_key = request('sort_key', 'id', 'created_at', 'updated_at', 'last_name', 'date_hired', 'total', 'start_date', 'file_name', 'file_date', 'name', 'email', 'created_by', 'section_name', 'work_schedule_name', 'holiday_name', 'time_data_id', 'date', 'time_off_type', 'time_off_code', 'balance_credit', 'step', 'grade', 'effectivity_date', 'job_info_effectivity_date', 'work_sched_effectivity_date', 'time_data_name');
    $sort_dir = request('sort_dir', 'descend') == 'descend' ? 'desc' : 'asc';
    if (is_array($sort_key)) {
        foreach($sort_key as $item) {
            $query = $query->orderBy($item, $sort_dir);
        }
    }
    else {
        $query = $query->orderBy($sort_key, $sort_dir);
    }
    $data = $query->get();

    if (sizeof($JSON_FIELDS) > 0) {
      $data->transform(function ($item, $key) use ($JSON_FIELDS) {
        foreach ($JSON_FIELDS as $json_field) {
          $item->{$json_field} = json_decode($item->{$json_field});
        }
        return $item;
      });
    }

    if (sizeof($BOOL_FIELDS) > 0) {
      $data->transform(function ($item, $key) use ($BOOL_FIELDS) {
        foreach ($BOOL_FIELDS as $bool_field) {
          $item->{$bool_field} = boolval($item->{$bool_field});
        }
        return $item;
      });
    }

    return array(
      'filters' => $filters,
      'sort_dir' => $sort_dir === 'desc' ? 'descend' : 'ascend',
      'sort_key' => $sort_key,
      'page' => $page,
      'take' => $take,
      'total' => $total,
      'q' => $q,
      'data' => $data,
      'ids' => $ids
    );
  }

  public function log_user_action($date, $time, $id, $name, $activity, $module)
  {
    $log = new \App\Log();

    $log->date = $date;
    $log->time = $time;
    $log->users_id = $id;
    $log->name = $name;
    $log->activity = $activity;
    $log->module = $module;

    $log->save();
    return;
  }

  public function list_user_actions(\App\Employee $employee)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    $actions = \DB::table('user_logs')->where('users_id', '=', $employee->users_id)->get();
    return $actions;
  }

  public function list_user_actions_ss()
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    $actions = \DB::table('user_logs')->where('users_id', '=', $this->me->id)->get();
    return $actions;
  }

  public function logInfoChange($empId, $category, $infoType, $old, $new)
  {
    if ($old !== $new) {
      $editHistory = new \App\EditHistory();
      $editHistory->employee_id = $empId;
      $editHistory->catgory = $category;
      $editHistory->information_type = $infoType;
      $editHistory->old = $old ? $old : '';
      $editHistory->new = $new ? $new : '';
      $editHistory->save();
    }
  }

  public static function parseHourMinute($time)
  {
    $times = explode(':', $time);
    $minutes = intval($times[1]);
    $hours = intval($times[0]);
    return [$hours, $minutes];
    // return $minutes * 60 + $hours * 3600;
  }

  public function validateDate($date, $format = 'Y-m-d')
  {
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
  }
}
