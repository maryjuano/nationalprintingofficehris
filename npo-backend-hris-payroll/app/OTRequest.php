<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

class OTRequest extends Model
{
    protected $table = 'ot_request';
    protected $fillable = [];
    protected $casts = [
        'schedule' => 'array'
    ];

    protected $dates = ['created_at', 'updated_at'];



    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function approvers()
    {
        return $this->hasManyDeep(
            '\App\ApprovalItemEmployee',
            ['\App\ApprovalRequest', '\App\ApprovalLevel', '\App\ApprovalItem'],
            [
                'id',
                'approval_request_id',
                'approval_level_id',
                'approval_item_id',
            ],
            [
                'approval_request_id',
                'id',
                'id',
                'id',
            ]
        );
    }

    public function shedule_checker($data)
    {
        $time_option = $data->time_option;
        if ($time_option === 1) {
            $table = \DB::table('fixed_daily_hours')->where('work_schedule_id', $data->id)->first();
            $breaks = \DB::table('break_times')->where('work_schedule_id', $data->id)
                ->select('start_time', 'end_time')
                ->where('status', 1)
                ->get();
            return $table ? array(
                "hours" => json_decode($table->daily_hours),
                "breaks" => $breaks,
                "time_option" => 1
            ) : null;
        } else if ($time_option === 2) {
            $table = \DB::table('fixed_daily_times')->where('work_schedule_id', $data->id)->first();
            $breaks = \DB::table('break_times')->where('work_schedule_id', $data->id)
                ->select('start_time', 'end_time')
                ->where('status', 1)
                ->get();
            return $table ? array(
                "start" => json_decode($table->start_times),
                "end" => json_decode($table->end_times),
                "breaks" => $breaks,
                "grace_period" => json_decode($table->grace_periods),
                "time_option" => 2
            ) : null;
        } else if ($time_option === 3) {
            $table = \DB::table('fixed_daily_times')->where('work_schedule_id', $data->id)->first();
            return $table ? array(
                "hours" => $table->flexible_weekly_hours,
                "time_option" => 3
            ) : null;
        }
    }

    public function list_generate($sched, $biometric, $employee_id)
    {
        if ($sched['time_option'] === 1) {
            return $this->time_option_one($sched, $biometric, $employee_id);
        } else if ($sched['time_option'] === 2) {
            return $this->time_option_two($sched, $biometric, $employee_id);
        }
    }

    public function time_option_two($sched, $biometric, $employee_id)
    {
        $start = $sched['start'];
        $end = $sched['end'];
        $arr_list = array();


        foreach ($biometric as $item) {

            $date = date('Y-m-d H:i:s', strtotime($item['date']));

            $break_in = \DB::table('biometrics')->where('employeeId', $employee_id)
                ->where('type', 1)
                ->where(function ($value) use ($date, $item) {
                    $datetime = new DateTime($item['date']);
                    $datetime->modify('+1 day');
                    $date2 = $datetime->format('Y-m-d H:i:s');

                    $value->where('attendance', '>=', date($date));
                    $value->where('attendance', '<', date($date2));
                })
                ->select('type', 'attendance')
                ->get();

            $break_out = \DB::table('biometrics')->where('employeeId', $employee_id)
                ->where('type', 2)
                ->where(function ($value) use ($date, $item) {
                    $datetime = new DateTime($item['date']);
                    $datetime->modify('+1 day');
                    $date2 = $datetime->format('Y-m-d H:i:s');

                    $value->where('attendance', '>=', date($date));
                    $value->where('attendance', '<', date($date2));
                })
                ->select('type', 'attendance')
                ->get();

            $out = date('H:i:s', strtotime($item['out']));
            $bio_out = strtotime($out);

            // "date": "2020-05-01",
            // "day": "friday",
            // "total": 10,
            // "in": "2020-05-01 07:00:00",
            // "out": "2020-05-01 17:00:00"

            if ($item['day'] === "monday") {
                $total = round(abs((strtotime($end->monday) - $bio_out) / 60 / 60), 2);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $total,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            } else if ($item['day'] === "friday") {
                $total = round(abs((strtotime($end->friday) - $bio_out) / 60 / 60), 2);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $total,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            } else if ($item['day'] === "tuesday") {
                $total = round(abs((strtotime($end->tuesday) - $bio_out) / 60 / 60), 2);
                round($total, 0);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $rounded,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            } else if ($item['day'] === "wednesday") {
                $total = round(abs((strtotime($end->wednesday) - $bio_out) / 60 / 60), 2);
                round($total, 0);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $rounded,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            } else if ($item['day'] === "thursday") {
                $total = round(abs((strtotime($end->thursday) - $bio_out) / 60 / 60), 2);
                round($total, 0);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $rounded,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            } else if ($item['day'] === "saturday") {
                $total = round(abs((strtotime($end->saturday) - $bio_out) / 60 / 60), 2);
                round($total, 0);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $rounded,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            } else if ($item['day'] === "sunday") {
                $total = round(abs((strtotime($end->sunday) - $bio_out) / 60 / 60), 2);
                round($total, 0);

                if ($total >= 2) {
                    array_push($arr_list, array(
                        "requested_by" => "None",
                        "date_submitted" => "---",
                        "date" => $item['date'],
                        "break_in" => $break_in,
                        "break_out" => $break_out,
                        "diff_rendered" => $rounded,
                        "total_rendered" => $item['total'],
                        "in" => $item['in'],
                        "out" => $item['out']
                    ));
                }
            }
        }

        return $arr_list;
    }

    public function time_option_one($sched, $biometric, $employee_id)
    {
        $arr_list = array();
        $hrs = $sched['hours'];

        foreach ($biometric as $item) {
            $date = date('Y-m-d H:i:s', strtotime($item['date']));

            $break_in = \DB::table('biometrics')->where('employeeId', $employee_id)
                ->where('type', 1)
                ->where(function ($value) use ($date, $item) {
                    $datetime = new DateTime($item['date']);
                    $datetime->modify('+1 day');
                    $date2 = $datetime->format('Y-m-d H:i:s');

                    $value->where('attendance', '>=', date($date));
                    $value->where('attendance', '<', date($date2));
                })
                ->select('type', 'attendance')
                ->get();

            $break_out = \DB::table('biometrics')->where('employeeId', $employee_id)
                ->where('type', 2)
                ->where(function ($value) use ($date, $item) {
                    $datetime = new DateTime($item['date']);
                    $datetime->modify('+1 day');
                    $date2 = $datetime->format('Y-m-d H:i:s');

                    $value->where('attendance', '>=', date($date));
                    $value->where('attendance', '<', date($date2));
                })
                ->select('type', 'attendance')
                ->get();

            switch ($item['day']) {
                case "monday":
                    if ($item['total'] > $hrs->monday) {
                        $diff = $item['total'] - $hrs->monday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                case "tuesday":
                    if ($item['total'] > $hrs->tuesday) {
                        $diff = $item['total'] - $hrs->tuesday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                case "wednesday":
                    if ($item['total'] > $hrs->wednesday) {
                        $diff = $item['total'] - $hrs->wednesday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                case "thursday":
                    if ($item['total'] > $hrs->thursday) {
                        $diff = $item['total'] - $hrs->thursday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                case "friday":
                    if ($item['total'] > $hrs->friday) {
                        $diff = $item['total'] - $hrs->friday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                case "saturday":
                    if ($item['total'] > $hrs->saturday) {
                        $diff = $item['total'] - $hrs->saturday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                case "sunday":
                    if ($item['total'] > $hrs->sunday) {
                        $diff = $item['total'] - $hrs->sunday;
                        if ($diff >=  2) {
                            array_push($arr_list, array(
                                "requested_by" => "None",
                                "date_submitted" => "---",
                                "date" => $item['date'],
                                "break_in" => $break_in,
                                "break_out" => $break_out,
                                "diff_rendered" => $diff,
                                "total_rendered" => $item['total'],
                                "in" => $item['in'],
                                "out" => $item['out']
                            ));
                        }
                    }
                    break;
                default:
                    return;
            }
        }

        return $arr_list;
    }

    public function biometric_validate($start, $end, $employee_id)
    {
        $start2 = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));

        $biometric = \DB::table('biometrics')->where('employeeId', $employee_id)
            ->where(function ($query) use ($start, $end) {
                $query->where('attendance', '>=', date($start));
                $query->where('attendance', '<=', date($end));
            })
            ->get()
            ->groupBy(function ($val) {
                return Carbon::parse($val->attendance)->format('Y-m-d');
            });

        $data = $this->check_Date($biometric, $start, $end);

        $result = [];
        foreach ($data as $row) {
            $date1 = strtotime($row['start']->attendance);
            $date2 = strtotime($row['end']->attendance);
            $diff = abs($date2 - $date1);
            $years = floor($diff / (365 * 60 * 60 * 24));
            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
            $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

            array_push(
                $result,
                array(
                    "date" => $row['date'],
                    "day" => strtolower(Carbon::parse($row['date'])->format('l')),
                    "total" => $hours,
                    "in" => $row['start']->attendance,
                    "out" => $row['end']->attendance
                )
            );
        };

        return $result;
    }

    public function check_Date($data, $start, $end)
    {
        $begin = new DateTime($start);
        $end = new DateTime($end);

        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);

        $array = [];

        foreach ($period as $dt) {
            $loop_range = $dt->format("Y-m-d");
            foreach ($data as $bio_key => $bio_value) {
                $date_bio =  Carbon::parse($bio_key)->format("Y-m-d");
                if ($date_bio === $loop_range) {
                    $date1 = $bio_value[0];
                    $date2 = $bio_value[count($bio_value) - 1];

                    array_push($array, array("date" => $bio_key, "start" => $date1, "end" => $date2));
                }
            }
        }


        return $array;
    }

    public function list_range($data, $start, $end, $employe_id)
    {
        $start2 = date('Y-m-d', strtotime($start));
        $end2 = date('Y-m-d', strtotime($end));

        $query = \DB::table('ot_request')->where('employee_id', request('employee_id'))
            ->where(function ($query) use ($start2, $end2) {
                $query->where('start', '>=', date($start2));
                $query->where('end', '<=', date($end2));
            })
            ->get();

        $result = [];
        foreach ($query as $row) {
            $arr = array();
            $arr['id'] = $row->id;
            $arr['employee_id'] = $row->employee_id;
            $arr['name'] = \DB::table('employees')->where('employees.id', $row->employee_id)
                ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
                ->select('personal_information.first_name', 'personal_information.middle_name', 'personal_information.last_name')
                ->first();
            $arr['start'] = $row->start;
            $arr['end'] = $row->end;
            $arr['status'] = $row->status;
            $arr['requested_by'] = $row->requested_by;
            $arr['schedule'] = json_decode($row->schedule);
            $start_date = Carbon::parse($row->start)->format('Y-m-d');
            $end_date = Carbon::parse($row->end)->format('Y-m-d');

            if ($start_date === $end_date) {
                $arr['biometric_time_in_out'] = \DB::table('biometrics')
                    ->where('employeeId', $row->employee_id)
                    ->whereDate('attendance', $start_date)
                    ->get();
            } else {
                $arr['biometric_time_in_out'] = \DB::table('biometrics')
                    ->where('employeeId', $row->employee_id)
                    ->whereBetween('attendance', [new Carbon($start_date), new Carbon($end_date)])
                    ->get();
            }

            $arr['is_requested'] = $row->is_requested;
            $arr['remarks'] = $row->remarks;
            $arr['created_at'] = $row->created_at;
            $arr['updated_at'] = $row->updated_at;
            $arr['approvers'] =  app('\App\Http\Controllers\ApprovalFlowController')->list_approvers($row->id, 4);

            array_push($result, $arr);
        }

        return $result;
    }
}
