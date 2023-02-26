<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\SectionController;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class DepartmentController extends Controller
{
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'department_name' => 'required|unique:departments',
            'code' => 'required|unique:departments',
            'sections' => 'required|array',
            'pap_code' => 'required'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'message' => $validator->errors()->first()], 400);
        }

        \DB::beginTransaction();
        try {
            $department = \App\Department::create($request->only(['department_name', 'code', 'pap_code']));
            $department->is_active = true;
            $department->created_by = $this->me->id;
            $department->updated_by = $this->me->id;
            $department->save();

            $this->update_sections($department, $request->input('sections'));

            $this->log_user_action(
                Carbon::parse($department->created_at)->toDateString(),
                Carbon::parse($department->created_at)->toTimeString(),
                $this->me->id,
                $this->me->name,
                "Created " . $department->department_name . " as Department",
                "HR & Payroll"
            );
            \DB::commit();
            $department->loadMissing('sections');
            return response()->json($department);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function read(Request $request, \App\Department $department)
    {
        $unauthorized = $this->is_not_authorized(['view_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $department->loadMissing('sections');
        return response()->json($department);
    }

    public function update(Request $request, \App\Department $department)
    {
        $unauthorized = $this->is_not_authorized(['edit_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'department_name' => "required|unique:departments,department_name,$department->id",
            'code' => "required|unique:departments,code,$department->id",
            'sections' => 'required|array',
            'pap_code' => 'required'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'message' => $validator->errors()->first()], 400);
        }

        \DB::beginTransaction();
        try {
            $department->fill($request->only(['department_name', 'code', 'pap_code']));
            $department->updated_by = $this->me->id;
            $department->save();

            $this->update_sections($department, $request->input('sections'));

            $this->log_user_action(
                Carbon::parse($department->created_at)->toDateString(),
                Carbon::now(),
                $this->me->id,
                $this->me->name,
                "Updated " . $department->department_name,
                "HR & Payroll"
            );
            \DB::commit();
            $department->loadMissing('sections');
            return response()->json($department);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function list(Request $request)
    {
        // $unauthorized = $this->is_not_authorized(['view_organization']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        $query = \App\Department::with([
            'sections'
        ])->withCount('employees');

        $ALLOWED_FILTERS = ['is_active'];
        $SEARCH_FIELDS = ['department_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function set_status(Request $request, \App\Department $department)
    {
        $unauthorized = $this->is_not_authorized(['toggle_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'status' => 'required'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $positions = \App\Position::where('department_id', $department->id)->get();
        if ($positions->count() > 0) {
            return response()->json(array("error" => "Update Failed!", "message" => "Sorry! A position is associated to this organization."), 400);
        }

        $employees = \App\EmploymentAndCompensation::where('department_id', $department->id)->get();
        if ($employees->count() > 0) {
            return response()->json(array("error" => "Update Failed!", "message" => "Sorry! There are employees under this organization."), 400);
        }

        $appflows_exist = \App\AppFlow::where('department_id', $department->id)->exists();
        if ($appflows_exist) {
            return response()->json(['error' => 'validation_failed', 'message' => 'Sorry! There are approval flows under this organization.'], 400);
        }

        \DB::beginTransaction();
        try {
            $department->is_active = $request->input('status');
            $department->save();
            \DB::commit();
            $this->log_user_action(
                Carbon::now(),
                Carbon::now(),
                $this->me->id,
                $this->me->name,
                $department->is_active == true ? "Activated Department " . $department->department_name : "Deactivated Department " . $department->department_name,
                "HR & Payroll"
            );
            return response()->json($department);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    private function update_sections($department, $sections = [])
    {
        foreach ($sections as $section) {
            if (isset($section['id'])) {
                $section_item = \App\Section::find($section['id']);
                if (isset($section['deleted']) && $section['deleted']) {
                    $employees_exist = \App\EmploymentAndCompensation::where('section_id', $section_item->id)->exists();
                    if ($employees_exist) {
                        $section_name = $section['section_name'];
                        throw new \Exception("$section_name cannot be deleted because there are employees assigned to this section");
                    }

                    $appflows_exist = \App\AppFlow::where('section_id', $section_item->id)->exists();
                    if ($appflows_exist) {
                        $section_name = $section['section_name'];
                        throw new \Exception("$section_name cannot be deleted because there are approval flows for this section");
                    }
                    $section_item->delete();
                } else {
                    $section_item->department_id = $department->id;
                    $section_item->section_name = $section['section_name'];
                    $section_item->save();
                }
            } else {
                $section_item = new \App\Section();
                if (!isset($section['deleted']) || !$section['deleted']) {
                    $section_item->department_id = $department->id;
                    $section_item->section_name = $section['section_name'];
                    $section_item->save();
                }
            }
        }
    }

    public function section_list()
    {
        // $unauthorized = $this->is_not_authorized(['view_organization']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }

        $query = \App\Section::select('*');

        $ALLOWED_FILTERS = ['department_id'];
        $SEARCH_FIELDS = ['section_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function section_edit(Request $request, \App\Section $section)
    {
        $unauthorized = $this->is_not_authorized(['edit_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }

        \DB::beginTransaction();
        try {
            $section->updated_by = $this->me->id;
            $section->section_name = request('section_name');
            $section->save();

            \DB::commit();
            return response()->json($section);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function section_read(Request $request, $department_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $table = \DB::table('sections')->where('department_id', $department_id);
        $query = $table->select('*');

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $data = $response['data'];
        $result = array();

        foreach ($data as $row) {
            $array = array();
            $array['id'] = $row->id;
            $array['department_id'] = $row->department_id;
            $array['section_name'] = $row->section_name;

            array_push($result, $array);
        }

        $response['data'] = $result;

        return response()->json($response);
    }

    public function section_status(Request $request, \App\Section $section)
    {
        $unauthorized = $this->is_not_authorized(['toggle_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }

        \DB::beginTransaction();
        try {
            $section->updated_by = $this->me->id;
            $section->status = request('status');
            $section->save();
            \DB::commit();
            return response()->json(array("data" => $section, "result" => "Updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function section_delete(Request $request, \App\Section $section)
    {
        $unauthorized = $this->is_not_authorized(['edit_organization']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $section->delete();
        return response()->json(array('_tmodel' => $_tmodel, 'result' => 'deleted'));
    }

    public function get_employees_for_department(Request $request, \App\Department $department)
    {
        // $unauthorized = $this->is_not_authorized(['view_organization']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        $query = \DB::table('employees')
            ->select(
                'employees.id',
                \DB::raw('CONCAT(
                    IFNULL(personal_information.last_name, \'\'),
                        \', \',
                        IFNULL(personal_information.first_name, \'\'),
                        \' \',
                        IFNULL(personal_information.middle_name, \'\')
                ) as name'),
                'employment_and_compensation.department_id',
                'employees.status'
            )
            ->leftJoin(
                'personal_information',
                'personal_information.employee_id',
                '=',
                'employees.id'
            )
            ->leftJoin(
                'employment_and_compensation',
                'employees.id',
                '=',
                'employment_and_compensation.employee_id'
            )
            ->where([
                ['employment_and_compensation.department_id', $department->id],
                ['employees.status', '1']
            ]);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = ['first_name', 'last_name', 'middle_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function get_employees_for_section(Request $request, \App\Section $section)
    {
        // $unauthorized = $this->is_not_authorized(['view_organization']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }

        $query = \DB::table('employees')
            ->select(
                'employees.id',
                \DB::raw('CONCAT(
                    IFNULL(personal_information.last_name, \'\'),
                        \', \',
                        IFNULL(personal_information.first_name, \'\'),
                        \' \',
                        IFNULL(personal_information.middle_name, \'\')
                ) as name')
            )
            ->leftJoin(
                'personal_information',
                'personal_information.employee_id',
                '=',
                'employees.id'
            )
            ->leftJoin(
                'employment_and_compensation',
                'employees.id',
                '=',
                'employment_and_compensation.employee_id'
            )
            ->where([
                ['employment_and_compensation.section_id', $section->id],
                ['employees.status', '1']
            ]);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = ['first_name', 'last_name', 'middle_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }
}
