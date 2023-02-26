<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Report;
use App\Reports\LeaveCard;
use App\Salary;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

use App\PersonalDataSheet;
use App\PDFGenerator;
use App\PersonalDataSheetPdfData;

class ReportContoller extends Controller
{
    public function pds(Request $request)
    {

        if ($request->input('id') === null) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'id is required'], 404);
        }

        if (\App\Employee::where('employees.id', $request->input('id'))->doesntExist()) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'Employee not found'], 404);
        }

        $pdf = new PDFGenerator(
            "PDS_CS_Form_No_212_Revised2017 v4.pdf",
            new PersonalDataSheetPdfData(new PersonalDataSheet($request->input('id')))
        );


        header('Access-Control-Allow-Headers: authorization');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Max-Age: 0');
        // $pdf->output();
        // $pdf->output();
        // return response('success', 200);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf');
    }


    public function generalTable(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['attendance_report']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'departments' => 'required|array',
            'departments.*' => 'required|exists:departments,id',
            'year' => 'required|date_format:Y',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $departments = $request->input('departments');
        $year = $request->input('year');
        $startDate = Carbon::createFromFormat('Y', $request->input('year'))->startOfYear()->toDateString();
        $endDate = Carbon::createFromFormat('Y', $request->input('year'))->endOfYear()->toDateString();

        $employees = \App\Employee::with([
            'personal_information',
            'employment_and_compensation.department',
            'dtrs' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('dtr_date', [$startDate, $endDate]);
                $q->leftJoin('dtr_submits', 'dtr_submits.id', 'dtrs.dtr_submit_id');
                $q->where('dtr_submits.status', 3);
                $q->select(
                    'dtrs.employee_id',
                    'dtrs.absence',
                    'dtrs.undertime_minutes',
                    'dtrs.late_minutes'
                );
            },
        ])
        ->whereHas('employment_and_compensation.department', function ($q) use ($departments) {
            $q->whereIn('id', $departments);
        })
        ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
        ->select(
            'employees.*',
            \DB::raw('
                CONCAT(
                    last_name,
                    ", ",
                    first_name,
                    " ",
                    middle_name,
                    " ",
                    CASE WHEN name_extension = "NA" THEN "" ELSE name_extension END
                ) AS name
            ')
        )
        ->orderBy('name');

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($employees, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $data = $response['data'];
        $summary = [];
        foreach ($data as $employee) {
            $totalAbsences = 0;
            $totalLates = 0;
            $totalUndertimes = 0;

            foreach ($employee['dtrs'] as $dtr) {
                $totalAbsences += $dtr['absence'];
                $totalLates += $dtr['late_minutes'] > 0 ? 1 : 0;
                $totalUndertimes += $dtr['undertime_minutes'] > 0 ? 1 : 0;
            }

            $summaryItem = [
                'id' => $employee->id,
                'name' => $employee->name,
                'division' => $employee->department,
                'absences' => $totalAbsences,
                'lates' => $totalLates,
                'undertimes' => $totalUndertimes,
            ];
            array_push($summary, $summaryItem);
        }
        unset($response['data']);
        return response()->json([
            'summary' => $summary,
            'meta' => $response
        ]);
    }

    public function general(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['attendance_report']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'generated_by' => 'required|string',
            'employees' => 'required|array',
            'employees.*' => 'required|exists:employees,id',
            'year' => 'required|date_format:Y',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $employees = $request->input('employees');
        $year = $request->input('year');
        $startDate = Carbon::createFromFormat('Y', $request->input('year'))->startOfYear()->toDateString();
        $endDate = Carbon::createFromFormat('Y', $request->input('year'))->endOfYear()->toDateString();

        $queryEmployees = \App\Employee::with([
            'personal_information',
            'employment_and_compensation.department',
            'dtrs' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('dtr_date', [$startDate, $endDate]);
                $q->leftJoin('dtr_submits', 'dtr_submits.id', 'dtrs.dtr_submit_id');
                $q->where('dtr_submits.status', 3);
                $q->select(
                    'dtrs.employee_id',
                    'dtrs.absence',
                    'dtrs.undertime_minutes',
                    'dtrs.late_minutes'
                );
            },
        ])
            ->whereIn('id', $employees)
            ->get();

        $summary = [];
        foreach ($queryEmployees as $employee) {
            $totalAbsences = 0;
            $totalLates = 0;
            $totalUndertimes = 0;

            foreach ($employee['dtrs'] as $dtr) {
                $totalAbsences += $dtr['absence'];
                $totalLates += $dtr['late_minutes'] > 0 ? 1 : 0;
                $totalUndertimes += $dtr['undertime_minutes'] > 0 ? 1 : 0;
            }

            $summaryItem = [
                'id' => $employee->id,
                'name' => $employee->name,
                'division' => $employee->department,
                'absences' => $totalAbsences,
                'lates' => $totalLates,
                'undertimes' => $totalUndertimes,
            ];
            array_push($summary, $summaryItem);
        }

        $image  = public_path() . '/images/logo.png';
        $data['summary'] = $summary;
        $data['formdetail'] = [
            'generated_by' => $request->input('generated_by'),
            'division' => collect($summary)->pluck('division')->unique()->flatten()->toArray(),
        ];
        view()->share('general', $data);
        view()->share('image', $image);
        $pdf = PDF::loadView('pdf.general')->setPaper('legal', 'portrait');
        return $pdf->stream('general.pdf');
        return $pdf->download('general.pdf');
        return view('pdf.general');
    }

    public function attendance_individual(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['attendance_report']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'generated_by' => 'required|string',
            'employees' => 'required|array',
            'employees.*' => 'required|exists:employees,id',
            'year' => 'required|date_format:Y',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $employees = $request->input('employees');
        $year = $request->input('year');
        $startDate = Carbon::createFromFormat('Y', $request->input('year'))->startOfYear()->toDateString();
        $endDate = Carbon::createFromFormat('Y', $request->input('year'))->endOfYear()->toDateString();

        $queryEmployees = \App\Employee::with([
            'personal_information',
            'employment_and_compensation.department',
            'dtrs' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('dtr_date', [$startDate, $endDate]);
                $q->leftJoin('dtr_submits', 'dtr_submits.id', 'dtrs.dtr_submit_id');
                $q->where('dtr_submits.status', 3);
                $q->select(
                    'dtrs.employee_id',
                    'dtrs.dtr_date',
                    'dtrs.absence',
                    'dtrs.undertime_minutes',
                    'dtrs.late_minutes'
                );
            },
        ])
            ->whereIn('id', $employees)
            ->get();

        $summary = [];
        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $monthName = [
            'January', 'February', 'March',
            'April', 'May', 'June', 'July',
            'August', 'September', 'October',
            'November', 'December'
        ];

        $summary = [];
        $monthToday = Carbon::now()->format('n');
        foreach ($queryEmployees as $employee) {
            $monthly_dtrs = collect($employee['dtrs'])->groupBy(function ($item) {
                return Carbon::createFromFormat('Y-m-d', $item->dtr_date)->format('n');
            });
            $month_data = [];
            foreach ($months as $month) {
                if ($monthToday < $month) {
                    continue;
                }
                if ($monthly_dtrs->has($month)) {
                    $current_month_dtrs = $monthly_dtrs->has($month) ? $monthly_dtrs[$month] : collect();
                    $totalAbsences = $current_month_dtrs->where('absence', '>', 0)->count();
                    $totalLates = $current_month_dtrs->where('late_minutes', '>', 0)->count();
                    $totalUndertimes = $current_month_dtrs->where('undertime_minutes', '>', 0)->count();

                    array_push($month_data, [
                        'month' => $monthName[$month - 1],
                        'year' => $year,
                        'absences' => $totalAbsences,
                        'lates' => $totalLates,
                        'undertimes' => $totalUndertimes,
                    ]);
                } else {
                    array_push($month_data, [
                        'month' => $monthName[$month - 1],
                        'year' => $year,
                        'absences' => 'not applicable',
                        'lates' => 'not applicable',
                        'undertimes' => 'not applicable',
                    ]);
                }
            }
            $summary[$employee->id] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'division' => $employee->department,
                'monthData' => $month_data,
            ];
        }

        $logo  = public_path() . '/images/logo.png';
        view()->share('logo', $logo);
        view()->share('data', $summary);
        view()->share('generatedBy', request('generated_by'));
        $pdf = PDF::loadView('pdf.attendance_individual')->setPaper('legal', 'portrait');
        return $pdf->stream('attendance_individual.pdf');
        return $pdf->download('attendance_individual.pdf');
        return view('pdf.attendance_individual');
    }

    public function FormA(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['gsis_a']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $data = [];
        $start = $request->input('start');
        $end = $request->input('end');
        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', $end)
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }
        $employees = \App\Employee::with([
            'personal_information',
            'employment_and_compensation',
            'employment_and_compensation.position',
            'employment_and_compensation.employee_type',
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->whereHas('employment_and_compensation', function ($q) use ($start, $end) {
                $q->whereBetween('date_hired', [new Carbon($start), new Carbon($end)]);
            })
            ->where('status', 1)
            ->get();
        if (request('type') === 'xlsx') {
            $headers = [
                'Content-Type' => 'application/xlsx'
            ];
            return $this->exportFormA($employees);
        } else {
            view()->share('employees', $employees);
            $pdf = PDF::loadView('pdf.formA')->setPaper('legal', 'landscape');
            return $pdf->stream('formA.pdf');
        }
    }

    public function FormD(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['gsis_d']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $start = date("Y-m-d", strtotime($request->input('start')));
        $end = date("Y-m-d", strtotime($request->input('end')));
        $from = date(request('start'));
        $to = date(request('end'));
        $query = \App\Employee::with([
            'personal_information',
            'employment_and_compensation',
            'offboard'
        ])
            ->has('offboard')
            ->whereHas('offboard', function ($q) use ($start, $end) {
                $q->whereBetween('effectivity', [$start, $end]);
            })
            ->get();

        if (request('type') === 'xlsx') {
            $headers = [
                'Content-Type' => 'application/xlsx'
            ];
            return $this->exportFormD($query);
        } else {
            view()->share('vals', [
                'data' => $query,
            ]);
            $pdf = PDF::loadView('pdf.formD')->setPaper('legal', 'landscape');
            return $pdf->stream('formD.pdf');
        }
    }

    public function FormE(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['gsis_e']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $employees = \App\Employee::with([
            'edit_history_first_name',
            'edit_history_last_name',
            'edit_history_middle_name',
            'edit_history_name_extension',
            'edit_history_zip_code',
            'edit_history_mobile_number',
            'edit_history_email_address',
            'edit_history_civil_status',
            'edit_history_date_of_birth',
            'edit_history_place_of_birth',
            'edit_history_position_old',
            'edit_history_position_new',
            'edit_history_employee_type_old',
            'edit_history_employee_type_new',
            'employment_and_compensation' => function ($q) {
                $q->select('employee_id', 'gsis_number');
            }
        ])
            ->where('status', 1)
            ->has('edit_history_first_name')
            ->orHas('edit_history_last_name')
            ->orHas('edit_history_middle_name')
            ->orHas('edit_history_name_extension')
            ->orHas('edit_history_zip_code')
            ->orHas('edit_history_mobile_number')
            ->orHas('edit_history_email_address')
            ->orHas('edit_history_civil_status')
            ->orHas('edit_history_date_of_birth')
            ->orHas('edit_history_place_of_birth')
            ->orHas('edit_history_position_old')
            ->orHas('edit_history_position_new')
            ->orHas('edit_history_employee_type_old')
            ->orHas('edit_history_employee_type_new')
            ->get();
        if (request('type') === 'xlsx') {
            $headers = [
                'Content-Type' => 'application/xlsx'
            ];
            return $this->exportFormE($employees);
        } else {
            view()->share('data', $employees);
            $pdf = PDF::loadView('pdf.formE')->setPaper('legal', 'landscape');
            return $pdf->stream('formE.pdf');
        }
    }

    public function formBtable()
    {
        $unauthorized = $this->is_not_authorized(['gsis_b']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $employees = \DB::table('employees')
            ->leftJoin('employment_and_compensation', 'employment_and_compensation.employee_id', 'employees.id')
            ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('employee_types', 'employee_types.id', 'employment_and_compensation.employee_type_id')
            ->leftJoin('salaries', 'salaries.grade', 'employment_and_compensation.salary_grade_id')
            ->leftJoin('positions', 'positions.id', 'employment_and_compensation.position_id')
            // ->whereDate('employment_and_compensation.date_hired', '>=', $start)
            // ->whereDate('employment_and_compensation.date_hired', '<=', $end)
            ->select(
                'employees.id',
                'personal_information.first_name',
                'personal_information.last_name',
                'personal_information.middle_name',
                'personal_information.name_extension',
                'employment_and_compensation.step_increment',
                'departments.code',
                'departments.department_name',
                'positions.item_number as position_code',
                'positions.position_name',
                'salaries.step',
                'salaries.effectivity_date',
                'employee_types.employee_type_name',
                'employment_and_compensation.date_hired'
            );
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($employees, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
    }

    public function formB(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['gsis_b']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $start = request('start');
        $end = request('end');

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', $end)
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employees = \App\Employee::with([
            'personal_information',
            'employment_and_compensation',
            'work_experience' => function ($q) {
                $q->orderBy('end_inclusive_date');
                // $q->limit(1);
            },
            'employment_and_compensation.employee_type' => function ($q) {
                $q->select('id', 'employee_type_name');
            },
            'employment_and_compensation.position' => function ($q) {
                $q->select('id', 'position_name', 'item_number');
            },
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->whereHas('employment_and_compensation', function ($query) use ($start, $end) {
                $query->whereBetween('date_hired', [new Carbon($start), new Carbon($end)]);
            })
            ->where('status', 1)
            ->get();

        if (request('type') === 'xlsx') {
            $headers = [
                'Content-Type' => 'application/xlsx'
            ];
            return $this->exportFormB($employees);
        } else {
            view()->share('vals', [
                'data' => $employees,
            ]);
            $pdf = PDF::loadView('pdf.formB')->setPaper('legal', 'landscape');
            return $pdf->stream('formB.pdf');
        }
    }

    public function formC(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['gsis_c']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $start = request('start');
        $end = request('end');

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', $end)
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employees = \App\Employee::with([
            'personal_information',
            'employment_and_compensation',
            'work_experience' => function ($q) {
                $q->orderBy('end_inclusive_date');
                $q->limit(1);
            },
            'employment_and_compensation.employee_type' => function ($q) {
                $q->select('id', 'employee_type_name');
            },
            'employment_and_compensation.position' => function ($q) {
                $q->select('id', 'position_name', 'item_number');
            },
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->whereHas('employment_and_compensation', function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    $q->whereBetween('job_info_effectivity_date', [new Carbon($start), new Carbon($end)]);
                })
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->whereBetween('period_of_service_start', [new Carbon($start), new Carbon($end)]);
                    });
            })
            ->where('status', 1)
            ->get();

        if (request('type') === 'xlsx') {
            $headers = [
                'Content-Type' => 'application/xlsx'
            ];
            return $this->exportFormC($employees);
        } else {
            view()->share('vals', [
                'data' => $employees,
            ]);
            $pdf = PDF::loadView('pdf.formC')->setPaper('legal', 'landscape');
            return $pdf->stream('formC.pdf');
        }
    }

    public function service_record(Request $request)
    {
        // $unauthorized = $this->is_not_authorized(['service_record']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }

        $validator_arr = [
            'employee_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $employee = \App\PersonalInformation::where('employee_id', $request->input('employee_id'))->firstOrFail();

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employee->date_of_birth_str = $employee->date_of_birth ? Carbon::createFromFormat('Y-m-d', $employee->date_of_birth)->format('F j, Y') : '';
        $work_experience = $this->getGovtWorkxp($request);
        $employment_history = \App\EmploymentHistory::where('employee_id', $request->input('employee_id'))
            ->orderBy('start_date')
            ->get();
        $current_employment = \App\EmploymentAndCompensation::with([
            'position',
            'department',
            'salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            },
            'employee_type'
        ])->where('employee_id', $request->input('employee_id'))
            ->orderBy('created_at', 'DESC')
            ->get()
            ->first();
        $currentLwop = \App\CurrentLwop::where('employee_id', $request->input('employee_id'))->first();
        $combined_work_xp = $work_experience->mergeRecursive($employment_history)->sortBy('start_date')->map(function ($item, $key) {
            $item->start_date = Carbon::createFromFormat('Y-m-d', $item->start_date)->format('m-d-Y');
            $item->end_date = !$item->end_date ? 'present' : Carbon::createFromFormat('Y-m-d', $item->end_date)->format('m-d-Y');
            return $item;
        });
        $report_generation_date = date('F j, Y');
        $empHistCount = count($combined_work_xp) + (!!$current_employment->position ? 1 : 0);

        $lwopsList = $employment_history->pluck('lwop')->toArray();
        array_push($lwopsList, $currentLwop->lwop ?? '');
        $lwop = strtolower(implode('', $lwopsList));
        $lwopHasValue = preg_match('/[a-z1-9,]/', $lwop); // convert to string then use reges to check values of lwop
        $noneArray = $this->printNoneDyanmic($empHistCount, $lwopHasValue);
        
        $signatory = \App\Signatories::where('report_name', 'Service Record (Document)')->first();
        
        view()->share('vals', [
            'employee_details' => $employee,
            'employment_history' => $combined_work_xp,
            'generation_date' => $report_generation_date,
            'current_employment' => $current_employment,
            'lwopEmpty' => $lwopHasValue, //0 if empty
            'noneArray' => $noneArray,
            'signatory' => $signatory,
            'currentLwop' => $currentLwop
        ]);

        if ($request->input('type') === 'pdf') {
            $pdf = PDF::loadView('pdf.service_record_doc')->setPaper('legal', 'portrait');
            return $pdf->stream('service_record_doc.pdf');
        } else {
            return $this->exportServiceRecordDocumentType(
                [
                    'employee_details' => $employee,
                    'employment_history' => $combined_work_xp,
                    'generation_date' => $report_generation_date,
                    'current_employment' => $current_employment,
                    'lwopEmpty' => $lwopHasValue, //0 if empty
                    'noneArray' => $noneArray,
                    'signatory' => $signatory,
                    'currentLwop' => $currentLwop
                ]
            );
        }
    }
    private function printNoneDyanmic($empHistCount, $lwopHasValue)
    {
        $interval = round($empHistCount / 4);
        $j = 0;
        $none = ['N', 'O', 'N', 'E'];
        $noneArray = [];
        if ($lwopHasValue === 0 && $empHistCount > 3) {
            for ($i = 1; $i <= $empHistCount; $i++) {
                $let = '';
                if (($empHistCount - $i <= 1 && $j < 4 && $empHistCount % 4 !== 0) || $empHistCount === 4 ||
                    ($empHistCount === $i && $empHistCount % 4 === 0) || ($i % $interval === 0 && $empHistCount - $i > 1)
                ) {
                    $let = $none[$j];
                    array_push($noneArray, $let);
                    $j++;
                } else {
                    array_push($noneArray, '');
                }
            }
        } else if ($empHistCount === 1) {
            $noneArray = ['NONE'];
        } else if ($lwopHasValue === 0 && $empHistCount <= 3) {
            $noneArray = ['NONE', 'NONE', 'NONE'];
        }
        return $noneArray;
    }
    public function gsis_service_record(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['service_record']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $validator_arr = [
            'employee_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employee = \App\PersonalInformation::where('employee_id', $request->input('employee_id'))->firstOrFail();
        $currentLwop = \App\CurrentLwop::where('employee_id', $request->input('employee_id'))->first();
        $employee->date_of_birth_str = $employee->date_of_birth ? Carbon::createFromFormat('Y-m-d', $employee->date_of_birth)->format('F j, Y') : '';
        $work_experience = $this->getGovtWorkxp($request);
        $employment_history = \App\EmploymentHistory::where('employee_id', $request->input('employee_id'))
            ->orderBy('start_date', 'ASC')
            ->get();
        $current_employment = \App\EmploymentAndCompensation::with([
            'position',
            'department',
            'salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            },
            'employee_type'
        ])->where('employee_id', $request->input('employee_id'))->orderBy('created_at', 'DESC')->get()->first();
        $combined_work_xp = $work_experience->mergeRecursive($employment_history)->sortBy('start_date')->map(function ($item, $key) {
            $item->start_date = Carbon::createFromFormat('Y-m-d', $item->start_date)->format('m-d-Y');
            $item->end_date = !$item->end_date ? 'present' : Carbon::createFromFormat('Y-m-d', $item->end_date)->format('m-d-Y');
            return $item;
        });
        $combined_work_xp[] = $current_employment;
        view()->share('vals', [
            'employee_details' => $employee,
            'employment_history' => $combined_work_xp,
            'current_employment' => $current_employment,
            'currentLwop' => $currentLwop
        ]);

        $pdf = PDF::loadView('pdf.service_record_gsis')->setPaper('letter', 'landscape');
        return $pdf->stream('service_record_gsis.pdf');
        // return $pdf->download('service_record_gsis.pdf');
        // return view('pdf.service_record_gsis');
    }

    private function getGovtWorkxp(Request $request)
    {
        $workxp = \App\WorkExperience::where('employee_id', $request->input('employee_id'))
            ->where('government_service', 1)
            ->select(
                'start_inclusive_date as start_date',
                'end_inclusive_date as end_date',
                'position_title as position_name',
                'monthly_salary as salary',
                'company as department_name',
                'status_of_appointment as status'
            )
            ->orderBy('start_date', 'ASC')
            ->get();
        return $workxp;
    }
    public function service_worker(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['service_record']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $employee_query = \DB::table('personal_information')
            ->where('personal_information.employee_id', request('type'))
            ->select(
                'personal_information.first_name',
                'personal_information.last_name',
                'personal_information.middle_name',
                'personal_information.gender',
                'personal_information.civil_status',
                'personal_information.name_extension',
                'personal_information.date_of_birth',
                'personal_information.place_of_birth'
            )
            ->first();

        $employee_details = array();
        $employee_details['first_name'] = $employee_query->first_name;
        $employee_details['middle_name'] = $employee_query->middle_name;
        $employee_details['last_name'] = $employee_query->last_name;
        $employee_details['gender'] = $employee_query->gender == 1 ? 'Male' : 'Female';
        switch ($employee_query->civil_status) {
            case 1:
                $employee_details['civil_status'] = 'Single';
                break;
            case 2:
                $employee_details['civil_status'] = 'Married';
                break;
            case 3:
                $employee_details['civil_status'] = 'Divorced';
                break;
            case 4:
                $employee_details['civil_status'] = 'Separated';
                break;
            case 5:
                $employee_details['civil_status'] = 'Widowed';
                break;
            default:
                $employee_details['civil_status'] = '';
        }
        $employee_details['date_of_birth'] = date('d-M-y', strtotime($employee_query->date_of_birth));
        $employee_details['place_of_birth'] = $employee_query->place_of_birth;

        $employment_history = \DB::table('employment_history')
            ->where('employment_history.employee_id', request('type'))
            ->leftJoin('positions', 'positions.id', 'employment_history.position_id')
            ->select(
                'employment_history.start_date',
                'employment_history.end_date',
                'positions.position_name',
                'employment_history.status',
                'positions.salary_grade', //salary
                'employment_history.step',
                'employment_history.section',
                'employment_history.branch',
                'employment_history.start_LWOP',
                'employment_history.end_LWOP',
                'employment_history.separation_date',
                'employment_history.separation_cause',
                'employment_history.remarks'
            )
            ->get();

        $position_history = array();
        foreach ($employment_history as $history_item) {
            $position = array();
            $position['start_date'] = date('n/j/y', strtotime($history_item->start_date));
            $position['end_date'] = date('n/j/y', strtotime($history_item->end_date));
            $position['position'] = $history_item->position_name;
            $position['status'] = $history_item->status;
            $salary_grade_steps = \DB::table('salaries')
                ->where('salaries.grade', $history_item->salary_grade)
                ->select('step')
                ->first();
            $salary_grade_steps = json_decode($salary_grade_steps->step);
            $position['salary'] = 'P ' . number_format($salary_grade_steps[(int) $history_item->step], 2, '.', ',');
            $section_query = \DB::table('sections')->where('id', (int) $history_item->section)
                ->select('section_name')
                ->first();
            $position['section'] = $section_query ? $section_query->section_name : null;
            // $position['section'] = $history_item->section;
            $position['branch'] = $history_item->branch;
            $position['start_LWOP'] = date('n/j/y', strtotime($history_item->start_LWOP));
            $position['end_LWOP'] = date('n/j/y', strtotime($history_item->end_LWOP));
            $position['separation_date'] = date('n/j/y', strtotime($history_item->separation_date));
            $position['separation_cause'] = $history_item->separation_cause;
            $position['remarks'] = $history_item->remarks;

            array_push($position_history, $position);
        }

        view()->share('vals', [
            'employee_details' => $employee_details,
            'employee_position_history' => $position_history,
        ]);

        $pdf = PDF::loadView('pdf.service_worker')->setPaper('letter', 'landscape');
        return $pdf->stream('service_worker.pdf');
        return $pdf->download('service_worker.pdf');
        return view('pdf.service_worker');
    }

    public function gsis_service(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['service_record']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $employee_query = \DB::table('personal_information')
            ->where('personal_information.employee_id', request('type'))
            ->select(
                'personal_information.first_name',
                'personal_information.last_name',
                'personal_information.middle_name',
                'personal_information.date_of_birth',
                'personal_information.place_of_birth'
            )
            ->first();

        $employee_details = array();
        $employee_details['first_name'] = strtoupper($employee_query->first_name);
        $employee_details['middle_name'] = strtoupper($employee_query->middle_name);
        $employee_details['last_name'] = strtoupper($employee_query->last_name);
        $employee_details['date_of_birth'] = date('d-M-y', strtotime($employee_query->date_of_birth));
        $employee_details['place_of_birth'] = strtoupper($employee_query->place_of_birth);

        $employment_history = \DB::table('employment_history')
            ->where('employment_history.employee_id', request('type'))
            ->leftJoin('positions', 'positions.id', 'employment_history.position_id')
            ->select(
                'employment_history.start_date',
                'employment_history.end_date',
                'positions.position_name',
                'employment_history.status',
                'positions.salary_grade', //salary
                'employment_history.step',
                'employment_history.section',
                'employment_history.branch',
                'employment_history.start_LWOP',
                'employment_history.end_LWOP',
                'employment_history.separation_date',
                'employment_history.separation_cause',
                'employment_history.separation_amount'
            )
            ->get();


        $position_history = array();
        foreach ($employment_history as $history_item) {
            $position = array();
            $position['start_date'] = date('n/j/y', strtotime($history_item->start_date));
            $position['end_date'] = date('n/j/y', strtotime($history_item->end_date));
            $position['position'] = $history_item->position_name;
            $salary_grade_steps = \DB::table('salaries')
                ->where('salaries.grade', $history_item->salary_grade)
                ->select('step')
                ->first();
            $salary_grade_steps = json_decode($salary_grade_steps->step);
            $position['salary'] = 'P ' . number_format($salary_grade_steps[(int) $history_item->step], 2, '.', ',');
            $section_query = \DB::table('sections')->where('id', (int) $history_item->section)
                ->select('section_name')
                ->first();
            $position['section'] = $section_query ? $section_query->section_name : null;
            // $position['section'] = $history_item->section;
            $position['status'] = $history_item->status;
            $position['branch'] = $history_item->branch;
            $position['start_LWOP'] = date('n/j/y', strtotime($history_item->start_LWOP));
            $position['end_LWOP'] = date('n/j/y', strtotime($history_item->end_LWOP));
            $position['separation_date'] = date('n/j/y', strtotime($history_item->separation_date));
            $position['separation_cause'] = $history_item->separation_cause;
            $position['separation_amount'] = 'P ' . number_format($history_item->separation_amount, 2, '.', ',');

            array_push($position_history, $position);
        }

        $report_generation_date = date('F j, Y');

        view()->share('vals', [
            'employee_details' => $employee_details,
            'employee_position_history' => $position_history,
            'generation_date' => $report_generation_date
        ]);

        $pdf = PDF::loadView('pdf.gsis_service')->setPaper('letter', 'portrait');
        return $pdf->stream('gsis_service.pdf');
        return $pdf->download('gsis_service.pdf');
        return view('pdf.gsis_service');
    }

    public function plantillaReport(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['plantilla_report_of_personnel']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $departments = \App\Department::with([
            'positions',
            'unfilled_positions'
        ])
            // ->whereHas('employment_and_compensation', function ($q) {
            //       $q->whereBetween(\DB::raw('date(job_info_effectivity_date)'), [request('start'), request('end')]);
            // })
            ->select('id', 'department_name')
            ->orderBy('sort')
            ->get();

        view()->share('departments', $departments);
        $pdf = PDF::loadView('pdf.plantilla'); //->setPaper('legal', 'landscape');
        return $pdf->stream('plantilla.pdf');
        return $pdf->download('plantilla.pdf');
        return view('pdf.plantilla');
    }

    public function plantillaPositions(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['plantilla_report_of_personnel']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $startDate = request('startDate');
        $endDate = request('endDate');

        // workaround recompute vacancy values in case they break
        $positions = \App\Position::where('is_active', 1)->get();
        foreach ($positions as $position) {
            $emp_comp = \App\EmploymentAndCompensation::where('position_id', $position->id)->first();
            if ($emp_comp) {
                $position->vacancy = \App\Position::VACANCY_FILLED;
                $position->save();
            }
        }


        $query = \DB::table('departments')->select('*')->orderBy('sort');
        // ->whereDate('startdate', '>=', $start)
        // ->whereDate('enddate', '<=', $end)

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $data = $response['data'];

        $result = array();
        foreach ($data as $department) {
            $item = array();
            $item['id'] = $department->id;
            $item['department_name'] = $department->department_name;
            $item['department_code'] = $department->code;
            //     $item['total_positions'] = \DB::table('positions')->where('positions.department_id', $department->id)->count();
            $item['positions_filled'] = \DB::table('positions')
                ->where('positions.department_id', $department->id)
                ->where('positions.vacancy', \App\Position::VACANCY_FILLED)
                ->where('positions.is_active', 1)
                ->count();
            $item['positions_unfilled'] = \DB::table('positions')
                ->where('positions.department_id', $department->id)
                ->where('positions.vacancy', \App\Position::VACANCY_UNFILLED)
                ->where('positions.is_active', 1)
                ->count();
            $item['total_positions'] = $item['positions_filled'] + $item['positions_unfilled'];
            array_push($result, $item);
        }

        $response['data'] = $result;
        return response()->json($response);
    }

    private function BoxStyle()
    {
        return [
            'font' => [
                'size' => 12,
                'bold' => false
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
    }

    private function exportFormA($data)
    {
        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($data) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/GSIS/FormA.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 13; //row start
            $worksheet->insertNewRowBefore($row, COUNT($data));
            foreach ($data as $item) {
                $employee_is_temporary = $item->employment_and_compensation->employee_type->id === \App\EmployeeType::COS ||
                    $item->employment_and_compensation->employee_type->id === \App\EmployeeType::JOB_ORDER;
                $salary_with_step = $employee_is_temporary ?
                    $item->employment_and_compensation->salary_rate :
                    $item->employment_and_compensation->salary->step[$item->employment_and_compensation->step_increment ?? 0];

                $position_name = $employee_is_temporary ? $item->employment_and_compensation->position_name :
                    $item->employment_and_compensation->position->position_name;

                $effectivity_date = Carbon::createFromFormat('Y-m-d', $item->employment_and_compensation->date_hired)->format('m/d/Y');

                $worksheet->getStyle('B' . $row . ':P' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('B' . $row)->setValue($item->personal_information->last_name);
                $worksheet->getCell('C' . $row)->setValue($item->personal_information->first_name);
                $worksheet->getCell('D' . $row)->setValue($item->personal_information->name_extension);
                $worksheet->getCell('E' . $row)->setValue($item->personal_information->middle_name);
                $worksheet->getCell('F' . $row)->setValue($item->personal_information->street);
                $worksheet->getCell('G' . $row)->setValue($item->personal_information->mobile_number[0] ?? '');
                $worksheet->getCell('H' . $row)->setValue($item->personal_information->email_address);
                $worksheet->getCell('I' . $row)->setValue($item->personal_information->gender_str);
                $worksheet->getCell('J' . $row)->setValue($item->personal_information->civil_status_str);
                $worksheet->getCell('K' . $row)->setValue(date_format(date_create($item->personal_information->date_of_birth), 'm/d/Y'));
                $worksheet->getCell('L' . $row)->setValue($item->personal_information->place_of_birth);
                $worksheet->getCell('M' . $row)->setValue(number_format($salary_with_step, 2, '.', ','));
                $worksheet->getCell('N' . $row)->setValue($effectivity_date);
                $worksheet->getCell('O' . $row)->setValue($position_name);
                $worksheet->getCell('P' . $row)->setValue($item->employment_and_compensation->employee_type->employee_type_name);
                $row++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'FormA' . '.xlsx"');

        return $streamedResponse;
    }

    private function exportFormB($data)
    {
        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($data) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/GSIS/FormB.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 13; //row start
            $worksheet->insertNewRowBefore($row, COUNT($data));
            foreach ($data as $item) {
                $employee_is_temporary = $item->employment_and_compensation->employee_type->id === \App\EmployeeType::COS ||
                    $item->employment_and_compensation->employee_type->id === \App\EmployeeType::JOB_ORDER;
                $salary_with_step = $employee_is_temporary ?
                    $item->employment_and_compensation->salary_rate :
                    $item->employment_and_compensation->salary->step[$item->employment_and_compensation->step_increment ?? 0];

                $position_name = $item->employment_and_compensation->position->position_name ??
                    $item->employment_and_compensation->position_name;

                $effectivity_date = Carbon::createFromFormat('Y-m-d', $item->employment_and_compensation->date_hired)->format('m/d/Y');

                $company = $item->work_experience->first() ? $item->work_experience->first()->company : '-';

                $worksheet->getStyle('B' . $row . ':L' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('B' . $row)->setValue($item->employment_and_compensation->gsis_number);
                $worksheet->getCell('C' . $row)->setValue($item->personal_information->last_name);
                $worksheet->getCell('D' . $row)->setValue($item->personal_information->first_name);
                $worksheet->getCell('E' . $row)->setValue($item->personal_information->name_extension);
                $worksheet->getCell('F' . $row)->setValue($item->personal_information->middle_name);
                $worksheet->getCell('G' . $row)->setValue($effectivity_date);
                $worksheet->getCell('H' . $row)->setValue(number_format($salary_with_step, 2, '.', ','));
                $worksheet->getCell('I' . $row)->setValue($position_name);
                $worksheet->getCell('J' . $row)->setValue($item->employment_and_compensation->employee_type->employee_type_name);
                $worksheet->getCell('K' . $row)->setValue($company);
                $worksheet->getCell('L' . $row)->setValue('NPO');
                $row++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'FormB' . '.xlsx"');

        return $streamedResponse;
    }

    private function exportFormC($data)
    {
        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($data) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/GSIS/FormC.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 13; //row start
            $worksheet->insertNewRowBefore($row, COUNT($data));
            foreach ($data as $item) {
                $employee_is_temporary = $item->employment_and_compensation->employee_type->id === \App\EmployeeType::COS ||
                    $item->employment_and_compensation->employee_type->id === \App\EmployeeType::JOB_ORDER;

                $salary_with_step = $employee_is_temporary ?
                    $item->employment_and_compensation->salary_rate :
                    $item->employment_and_compensation->salary->step[$item->employment_and_compensation->step_increment ?? 0];

                $position_name = $item->employment_and_compensation->position->position_name ??
                    $item->employment_and_compensation->position_name;

                $effectivity_date = '';
                if ($item->employment_and_compensation->job_info_effectivity_date) {
                    $effectivity_date = Carbon::createFromFormat('Y-m-d', $item->employment_and_compensation->job_info_effectivity_date)->format('m/d/Y');
                } else if ($item->employment_and_compensation->period_of_service_start) {
                    $effectivity_date = Carbon::createFromFormat('Y-m-d', $item->employment_and_compensation->period_of_service_start)->format('m/d/Y');
                }

                $company = $item->work_experience->first() ? $item->work_experience->first()->company : '';

                $worksheet->getStyle('B' . $row . ':J' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('B' . $row)->setValue($item->employment_and_compensation->gsis_number);
                $worksheet->getCell('C' . $row)->setValue($item->personal_information->last_name);
                $worksheet->getCell('D' . $row)->setValue($item->personal_information->first_name);
                $worksheet->getCell('E' . $row)->setValue($item->personal_information->name_extension);
                $worksheet->getCell('F' . $row)->setValue($item->personal_information->middle_name);
                $worksheet->getCell('G' . $row)->setValue(number_format($salary_with_step, 2, '.', ','));
                $worksheet->getCell('H' . $row)->setValue($effectivity_date);
                $worksheet->getCell('I' . $row)->setValue($position_name);
                $worksheet->getCell('J' . $row)->setValue($item->employment_and_compensation->employee_type->employee_type_name);
                $row++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'FormC' . '.xlsx"');

        return $streamedResponse;
    }

    private function exportFormD($data)
    {
        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($data) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/GSIS/FormD.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 13; //row start
            $worksheet->insertNewRowBefore($row, COUNT($data));
            foreach ($data as $item) {
                $worksheet->getStyle('B' . $row . ':I' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('B' . $row)->setValue($item->employment_and_compensation->gsis_number != '0' ?
                    $item->employment_and_compensation->gsis_number : '');
                $worksheet->getCell('C' . $row)->setValue($item->personal_information->last_name);
                $worksheet->getCell('D' . $row)->setValue($item->personal_information->first_name);
                $worksheet->getCell('E' . $row)->setValue($item->personal_information->name_extension);
                $worksheet->getCell('F' . $row)->setValue($item->personal_information->middle_name);
                $worksheet->getCell('G' . $row)->setValue($item->offboard->reason);
                $worksheet->getCell('H' . $row)->setValue(date_format(date_create($item->offboard->effectivity), 'M. d, Y'));
                $worksheet->getCell('I' . $row)->setValue($item->offboard->remarks ?? 'N/A');
                $row++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'FormD' . '.xlsx"');

        return $streamedResponse;
    }

    private function exportFormE($data)
    {
        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($data) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/GSIS/FormE.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 14; //row start
            $worksheet->insertNewRowBefore($row, COUNT($data));
            foreach ($data as $item) {
                $worksheet->getStyle('B' . $row . ':Z' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('B' . $row)->setValue($item->employment_and_compensation->gsis_number);
                $worksheet->getCell('C' . $row)->setValue($item->edit_history_last_name ? $item->edit_history_last_name->old : '');
                $worksheet->getCell('D' . $row)->setValue($item->edit_history_last_name ? $item->edit_history_last_name->new : '');
                $worksheet->getCell('E' . $row)->setValue($item->edit_history_first_name ? $item->edit_history_first_name->old : '');
                $worksheet->getCell('F' . $row)->setValue($item->edit_history_first_name ? $item->edit_history_first_name->new : '');
                $worksheet->getCell('G' . $row)->setValue($item->edit_history_name_extension ? $item->edit_history_name_extension->old : '');
                $worksheet->getCell('H' . $row)->setValue($item->edit_history_name_extension ? $item->edit_history_name_extension->new : '');
                $worksheet->getCell('I' . $row)->setValue($item->edit_history_middle_name ? $item->edit_history_middle_name->old : '');
                $worksheet->getCell('J' . $row)->setValue($item->edit_history_middle_name ? $item->edit_history_middle_name->new : '');
                $worksheet->getCell('K' . $row)->setValue($item->edit_history_zip_code ? $item->edit_history_zip_code->old : '');
                $worksheet->getCell('L' . $row)->setValue($item->edit_history_zip_code ? $item->edit_history_zip_code->new : '');
                $worksheet->getCell('M' . $row)->setValue($item->edit_history_mobile_number ? $item->edit_history_mobile_number->old : '');
                $worksheet->getCell('N' . $row)->setValue($item->edit_history_mobile_number ? $item->edit_history_mobile_number->new : '');
                $worksheet->getCell('O' . $row)->setValue($item->edit_history_email_address ? $item->edit_history_email_address->old : '');
                $worksheet->getCell('P' . $row)->setValue($item->edit_history_email_address ? $item->edit_history_email_address->new : '');
                $worksheet->getCell('Q' . $row)->setValue($item->edit_history_civil_status ?
                    $this->civil_status_to_str($item->edit_history_civil_status->old) : '');
                $worksheet->getCell('R' . $row)->setValue($item->edit_history_civil_status ?
                    $this->civil_status_to_str($item->edit_history_civil_status->new) : '');
                $worksheet->getCell('S' . $row)->setValue(
                    $item->edit_history_date_of_birth ? date_format(date_create($item->edit_history_date_of_birth->old), "Y-m-d") : ''
                );
                $worksheet->getCell('T' . $row)->setValue(
                    $item->edit_history_date_of_birth ? date_format(date_create($item->edit_history_date_of_birth->new), "Y-m-d") : ''
                );
                $worksheet->getCell('U' . $row)->setValue($item->edit_history_place_of_birth ? $item->edit_history_place_of_birth->old : '');
                $worksheet->getCell('V' . $row)->setValue($item->edit_history_place_of_birth ? $item->edit_history_place_of_birth->new : '');
                $worksheet->getCell('W' . $row)->setValue($item->edit_history_position_old ? $item->edit_history_position_old->position_name : '');
                $worksheet->getCell('X' . $row)->setValue($item->edit_history_position_new ? $item->edit_history_position_new->position_name : '');
                $worksheet->getCell('Y' . $row)->setValue($item->edit_history_employee_type_old ? $item->edit_history_employee_type_old->employee_type_name : '');
                $worksheet->getCell('Z' . $row)->setValue($item->edit_history_employee_type_new ? $item->edit_history_employee_type_new->employee_type_name : '');

                $row++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'FormE' . '.xlsx"');

        return $streamedResponse;
    }

    private function civil_status_to_str($val)
    {
        switch ($val) {
            case 1:
                return 'Single';
            case 2:
                return 'Married';
            case 3:
                return 'Divorced';
            case 4:
                return 'Seperated';
            case 5:
                return 'Widowed';
        }
    }

    public function formatDate($start, $end)
    {

        $startToTime = strtotime($start);
        $month = date("F", $startToTime);
        $dayStart = date("j", $startToTime);
        $dayEnd = date("j", strtotime($end));
        $year = date('Y', $startToTime);
        $formatted = $month . ' ' . $dayStart . '-' . $dayEnd . ',' . $year;
        return 'for the period of ' . $formatted;
    }


    private function exportServiceRecordDocumentType($data)
    {
        $current_employment = [];
        $rowData = [];
        if (!!$data['current_employment']->position) {
            $current_employment['start_date'] =  date_format(
                date_create($data['current_employment']->job_effectivity_date),
                'm-d-Y'
            );
            $current_employment['end_date'] = 'present';
            $current_employment['position_name'] = $data['current_employment']->position->position_name;
            $current_employment['status'] = $data['current_employment']->employee_type->employee_type_name;
            $current_employment['salary'] = number_format($data['current_employment']->salary->step[$data['current_employment']->step_increment]* 12, 2);
            $current_employment['department_name'] = 'NPO';
            $current_employment['branch'] = 'Nat\'l';
            $current_employment['lwop'] = $data['currentLwop']->lwop ?? '';
    
            $rowData = array_merge(
                $data['employment_history']->toArray(),
                [$current_employment]
            );
        } else {
            $rowData = $data['employment_history']->toArray();
        }
        
        $signatory = $data['signatory']->signatories;

        $streamedResponse = new StreamedResponse();
        
        $streamedResponse->setCallback(function () use ($data, $rowData, $signatory) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/SR_Document_Type.xlsx';
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setLoadSheetsOnly('FRONT');
            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();

            $employee_details = $data['employee_details'];
            $FIX_ROW_COUNT = 43;
            $row = 18; // start row

            $worksheet->getCell('B5')->setValue($employee_details->last_name);
            $worksheet->getCell('D5')->setValue($employee_details->first_name);
            $worksheet->getCell('F5')->setValue($employee_details->middle_name);
            $worksheet->getCell('B7')->setValue($employee_details->date_of_birth_str);
            $worksheet->getCell('E7')->setValue($employee_details->place_of_birth);

            $worksheet->getCell('I71')->setValue(
                ($signatory[0]['name'] ?? '')
            );
            $worksheet->getCell('I74')->setValue(
                ($signatory[0]['title'] ?? '')
            );

            //dividing in 42 for 2 page
            $chunk_array = array_chunk($rowData, 42);

            for ($i = 0; $i < $FIX_ROW_COUNT; $i++) {
                if (isset($chunk_array[0][$i])) {
                    $worksheet->getRowDimension($row)->setRowHeight(20);
                    $worksheet->getCell('A' . $row)->setValue(
                        ($chunk_array[0][$i]['start_date'] ?? '')
                    );
                    $worksheet->getCell('B' . $row)->setValue(
                        ($chunk_array[0][$i]['end_date'] ?? '')
                    );
                    $worksheet->getCell('C' . $row)->setValue(
                        ($chunk_array[0][$i]['position_name'] ?? '')
                    );
                    $worksheet->getCell('D' . $row)->setValue(
                        ($chunk_array[0][$i]['status'] ?? '')
                    );
                    $worksheet->getCell('E' . $row)->setValue(
                        $chunk_array[0][$i]['salary'] ? number_format(str_replace(',', '', $chunk_array[0][$i]['salary']), 2) : ''
                    );
                    $worksheet->getCell('F' . $row)->setValue(
                        ($chunk_array[0][$i]['department_name'] ?? '')
                    );
                    $worksheet->getCell('G' . $row)->setValue(
                        ($chunk_array[0][$i]['branch'] ?? '')
                    );
                    $worksheet->getCell('H' . $row)->setValue(
                        ($data['lwopEmpty'] == 0 ? $data['noneArray'][$i] : $chunk_array[0][$i]['lwop'] ?? '')
                    );
                    $worksheet->getCell('I' . $row)->setValue(
                        ((isset($chunk_array[0][$i]['separation_date']) && $chunk_array[0][$i]['separation_date'] != null) ? date_format(date_create($chunk_array[0][$i]['separation_date']),'m-d-Y') : '')
                    );
                    $worksheet->getCell('J' . $row)->setValue(
                        ($chunk_array[0][$i]['separation_cause'] ?? '')
                    );
                    $worksheet->getCell('K' . $row)->setValue(
                        ($chunk_array[0][$i]['separation_amount_received'] ?? '')
                    );
                }
                
                if (COUNT($chunk_array[0]) < 42 && $i == (COUNT($chunk_array[0]))) {
                    $worksheet->mergeCells('D' . $row . ':G' . $row);
                    $worksheet->getCell('D' . $row)->setValue(
                        '- X - X - X - NOTHING FOLLOWS - X - X - X -'
                    );
                }

                if (COUNT($chunk_array[0]) == 42 && $i == 42) {
                    //end of page 1
                    if (isset($chunk_array[1])) {
                        $worksheet->mergeCells('A' . $row . ':K' . $row);
                        $xxx = "";
                        for ($x = 0; $x < 90; $x++) {
                            $xxx = $xxx . 'X';
                        }
                        $worksheet->getCell('A' . $row)->setValue($xxx);
                    } else {
                        $worksheet->mergeCells('D' . $row . ':G' . $row);
                        $worksheet->getCell('D' . $row)->setValue(
                            '- X - X - X - NOTHING FOLLOWS - X - X - X -'
                        );
                    }
                }


                if (isset($chunk_array[1][$i])) {
                    $worksheet->getRowDimension($row)->setRowHeight(20);
                    $worksheet->getCell('L' . $row)->setValue(
                        ($chunk_array[1][$i]['start_date'] ?? '')
                    );
                    $worksheet->getCell('M' . $row)->setValue(
                        ($chunk_array[1][$i]['end_date'] ?? '')
                    );
                    $worksheet->getCell('N' . $row)->setValue(
                        ($chunk_array[1][$i]['position_name'] ?? '')
                    );
                    $worksheet->getCell('O' . $row)->setValue(
                        ($chunk_array[1][$i]['status'] ?? '')
                    );
                    $worksheet->getCell('P' . $row)->setValue(
                        ($chunk_array[1][$i]['salary'] ?? '')
                    );
                    $worksheet->getCell('Q' . $row)->setValue(
                        ($chunk_array[1][$i]['department_name'] ?? '')
                    );
                    $worksheet->getCell('R' . $row)->setValue(
                        ($chunk_array[1][$i]['branch'] ?? '')
                    );
                    $worksheet->getCell('S' . $row)->setValue(
                        ($data['lwopEmpty'] == 0 ? $data['noneArray'][$i] : $chunk_array[1][$i]['lwop'] ?? '')
                    );
                    $worksheet->getCell('T' . $row)->setValue(
                        ($chunk_array[1][$i]['separation_date'] ?? '')
                    );
                    $worksheet->getCell('U' . $row)->setValue(
                        ($chunk_array[1][$i]['separation_cause'] ?? '')
                    );
                    $worksheet->getCell('V' . $row)->setValue(
                        ($chunk_array[1][$i]['separation_amount_received'] ?? '')
                    );
                }

                if (isset($chunk_array[1]) && COUNT($chunk_array[1]) < 42 && $i == (COUNT($chunk_array[1]))) {
                    $worksheet->mergeCells('O' . $row . ':R' . $row);
                    $worksheet->getCell('O' . $row)->setValue(
                        '- X - X - X - NOTHING FOLLOWS - X - X - X -'
                    );
                }

                $row++;
            }
            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'Service_Record_Document_Type' . '.xlsx"');

        return $streamedResponse;
    }

    public function leaveCard(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'ids' => 'required',
            'year' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation_failed', 'messages' => $validator->errors()->all()
            ], 400);
        }

        $employee_ids = $request->input('ids');
        $leaveCards = [];
        foreach ($employee_ids as $employee_id) {
            $leaveCards[] = new LeaveCard($employee_id, $request->input('year'));
        }
        $pdf = PDF::loadView('pdf.leaveCard', compact('leaveCards'))->setPaper('legal', 'portrait');
        return $pdf->stream('leaveCard.pdf');
    }



    private function addUnderTimeLate($lates, $undertime)
    {
        if ($lates == '00:00') {
            return $undertime;
        } else if ($undertime == '00:00') {
            return $lates;
        } else {
            $time = (strtotime($undertime) - strtotime("00:00:00"));
            $result = date("H:i", strtotime($lates) + $time);
            return $result;
        }
    }
    private function isWeekEnd($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 0 || $weekDay == 6);
    }
}
