<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    private function save_or_save_and_activate(Request $request, \App\Employee $employee, $is_save_only, $is_new)
    {
        if (!$is_save_only && !$is_new && $employee->status == 1) {
            return response()->json(['error' => 'Employee already activated.', 'messages' => "Employee already activated."], 400);
        }
        $exlucsionRule = 'exclude_unless:employee_type_id,1|exclude_unless:employee_type_id,6|required';
        if (!$is_save_only) {
            $validator_arr = [
                'general' => 'required',
                'employment_and_compensation' => 'required',
                'time_off_balance' => $exlucsionRule,
                'system_information' => 'required_if:$is_save_only,==,false'
            ];
        } else {
            $validator_arr = [];
        }
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $employee->save();
            if ($employee->id) {
                $edit_users_db = 0;

                if ($request->has('general')) {
                    $edit_users_db = 1; //edit users_db too
                    $emptype = 1;
                    if ($request->filled('employment_and_compensation') && isset($request->input('employment_and_compensation')['employee_type_id'])) {
                        $emptype = $request->input('employment_and_compensation')['employee_type_id'];
                    } else if ($employee->employment_and_compensation) {
                        $emptype = $employee->employment_and_compensation->employee_type_id;
                    }
                    $this->add_general($request->input('general'), $is_save_only, $is_new, $employee->id, $emptype);
                }

                if ($request->has('employment_and_compensation')) {
                    $edit_users_db = 1; //edit users_db too
                    if(!empty($request['employment_and_compensation']) && isset($request->input('employment_and_compensation')['employee_type_id'])){
                        $emptype = $request->input('employment_and_compensation')['employee_type_id'];
                    }
                    $this->add_employment_and_compensation($request->input('employment_and_compensation'), $is_save_only, $is_new, $employee, $emptype);
                }

                if ($request->has('cases')) {
                    $this->add_cases($request->input('cases'), $is_save_only, $is_new, $employee);
                }

                if ($request->has('attachments')) {
                    $this->add_attachments($request->input('attachments'), $is_save_only, $is_new, $employee);
                }

                if ($request->has('time_off_balance') && !in_array($emptype, [2, 5])) {
                    $this->add_time_off_balance($request->input('time_off_balance'), $is_save_only, $is_new, $employee->id);
                }

                if ($request->has('system_information')) {
                    $edit_users_db = 1; //edit users_db too
                    $this->add_system_information($request->input('system_information'), $is_save_only, $is_new, $employee);

                    if (!$is_save_only) {
                        $employee->status = 1;
                        $create_user = $this->create_user($request, $employee, $is_save_only, $is_new);
                        if (property_exists($create_user, 'user')) {
                            $employee->users_id = $create_user->user->id;
                        } else {
                            throw new \Exception($create_user->error);
                        }
                    }
                }

                if (!$is_new && $is_save_only && $employee->status == 1 && $edit_users_db == 1) {
                    // if EDIT and SAVE and already ACTIVATED
                    // update the user on npo_users_db
                    // return $update_user;
                    $update_user = $this->update_user($request, $employee->users_id);
                    if (property_exists($update_user, 'error')) {
                        throw new \Exception($update_user->error);
                    }
                }
            }

            //activate part
            if (!$is_save_only) {
                $employee->status = 1;
            } elseif ($is_save_only && $is_new) {
                $employee->status = 0;
            }

            if ($is_new) {
                $employee->created_by = $this->me->id;
                $employee->updated_by = $this->me->id;

                $this->log_user_action(
                    Carbon::parse($employee->created_at)->toDateString(),
                    Carbon::parse($employee->created_at)->toTimeString(),
                    $this->me->id,
                    $this->me->name,
                    "Created " . $employee->personal_information->first_name . " " . $employee->personal_information->last_name . " as New Employee",
                    "HR & Payroll"
                );
            } else {
                $employee->updated_by = $this->me->id;

                $this->log_user_action(
                    Carbon::parse($employee->updated_at)->toDateString(),
                    Carbon::parse($employee->updated_at)->toTimeString(),
                    $this->me->id,
                    $this->me->name,
                    "Modified Record of " . $employee->personal_information->first_name . " " . $employee->personal_information->last_name,
                    "HR & Payroll"
                );
            }

            $employee->save();

            if (!$is_save_only) {
                \App\Http\Controllers\AppFlowController::assignNewEmployeeToApprovalFlows($employee);
            }

            \DB::commit();
            return response()->json(array("data" => $employee, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'validation_failed', 'message' => $e->getMessage()], 400);
        }
    }

    public function save(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['add_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->save_or_save_and_activate($request, new \App\Employee(), true, true);
    }

    public function save_and_activate(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['add_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->save_or_save_and_activate($request, new \App\Employee(), false, true);
    }

    public function edit_save(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->save_or_save_and_activate($request, $employee, true, false);
    }

    public function edit_and_activate(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['add_employee', 'edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->save_or_save_and_activate($request, $employee, false, false);
    }

    public function get_employee_status(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return response()->json($employee->status);
    }

    public function add_general($general, $is_save_only, $is_new, $employee_id, $emptype)
    {
        if (array_key_exists('personal_information', $general)) {
            $this->add_personal_information($general['personal_information'], $is_save_only, $is_new, $employee_id);
        }

        if (array_key_exists('family_background', $general)) {
            $this->add_family_background($general['family_background'], $is_save_only, $is_new, $employee_id, $emptype);
        }

        if (array_key_exists('educational_background', $general)) {
            $this->add_educational_background($general['educational_background'], $is_save_only, $is_new, $employee_id);
        }

        if (array_key_exists('civil_service', $general)) {
            $this->add_civil_service($general['civil_service'], $is_save_only, $is_new, $employee_id);
        }

        if (array_key_exists('work_experience', $general)) {
            $this->add_work_experience($general['work_experience'], $is_save_only, $is_new, $employee_id);
        }

        if (array_key_exists('voluntary_work', $general)) {
            $this->add_voluntary_work($general['voluntary_work'], $is_save_only, $is_new, $employee_id);
        }

        if (array_key_exists('training_program', $general)) {
            $this->add_training_program($general['training_program'], $is_save_only, $is_new, $employee_id);
        }

        if (array_key_exists('other_information', $general)) {
            $this->add_other_information($general['other_information'], $is_save_only, $is_new, $employee_id);
        }
        if (array_key_exists('govt_id', $general)) {
            $this->add_government_id($general['govt_id'], $is_save_only, $is_new, $employee_id);
            if (array_key_exists('references', $general['govt_id'])) {
                $this->add_references($general['govt_id']['references'], $is_save_only, $is_new, $employee_id);
            }
        }

        if (array_key_exists('questionnaire', $general)) {
            $this->add_questionnaire($general['questionnaire'], $is_save_only, $is_new, $employee_id);
        }
    }

    public function add_personal_information($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_save_only) {
            $validator = Validator::make($data, [
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'date_of_birth' => 'required',
                'place_of_birth' => 'required',
                'civil_status' => 'required',
                'gender' => 'required',
                'citizenship' => 'required',
                'country' => 'required_if:dual_citizenship,true',
                'by' => 'required_if:dual_citizenship,true',
                'barangay' => 'required',
                'city' => 'required',
                'province' => 'required',
                'zip_code' => 'required',
                'same_addresses' => 'required',
                'p_barangay' => 'required_if:same_addresses,true',
                'p_city' => 'required_if:same_addresses,true',
                'p_province' => 'required_if:same_addresses,true',
                'p_zip_code' => 'required_if:same_addresses,true',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        }else{
            $validator = Validator::make($data, [
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required'
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        }

        if ($is_new) {
            $personal_info = new \App\PersonalInformation();
            $personal_info->employee_id = $employee_id;
        } else {
            $personal_info = \App\PersonalInformation::where('employee_id', '=', $employee_id)->first();
        }

        if (array_key_exists('first_name', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personl_information', 'first_name', $personal_info->first_name, $data['first_name']);
            }
            $personal_info->first_name = $data['first_name'];
        }
        if (array_key_exists('middle_name', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personl_information', 'middle_name', $personal_info->middle_name, $data['middle_name']);
            }
            $personal_info->middle_name = $data['middle_name'];
        }
        if (array_key_exists('last_name', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personl_information', 'last_name', $personal_info->last_name, $data['last_name']);
            }
            $personal_info->last_name = $data['last_name'];
        }
        if (array_key_exists('name_extension', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personl_information', 'name_extension', $personal_info->name_extension, $data['name_extension']);
            }
            $personal_info->name_extension = $data['name_extension'];
        }
        if (array_key_exists('date_of_birth', $data) && $data['date_of_birth'] != null) {
            if (!$is_new) {
                $this->logInfoChange(
                    $employee_id,
                    'personal_information',
                    'date_of_birth',
                    Carbon::parse($personal_info->date_of_birth)->toDateString(),
                    Carbon::parse($data['date_of_birth'])->toDateString()
                );
            }
            $personal_info->date_of_birth = Carbon::parse($data['date_of_birth'])->toDateString();
        }
        if (array_key_exists('place_of_birth', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personal_information', 'place_of_birth', $personal_info->place_of_birth, $data['place_of_birth']);
            }
            $personal_info->place_of_birth = $data['place_of_birth'];
        }
        if (array_key_exists('civil_status', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personal_information', 'civil_status', $personal_info->civil_status, $data['civil_status']);
            }
            $personal_info->civil_status = $data['civil_status'];
        }
        if (array_key_exists('height', $data)) $personal_info->height = $data['height'];
        if (array_key_exists('weight', $data)) $personal_info->weight = $data['weight'];
        if (array_key_exists('blood_type', $data)) $personal_info->blood_type = $data['blood_type'];
        if (array_key_exists('gender', $data)) $personal_info->gender = $data['gender'];

        if (array_key_exists('citizenship', $data)) $personal_info->citizenship = $data['citizenship'];
        if (array_key_exists('dual_citizenship', $data)) $personal_info->dual_citizenship = $data['dual_citizenship'];
        if (array_key_exists('country', $data)) $personal_info->country = $data['country'];
        if (array_key_exists('by', $data)) $personal_info->by = $data['by'];

        if (array_key_exists('house_number', $data)) $personal_info->house_number = $data['house_number'];
        if (array_key_exists('street', $data)) $personal_info->street = $data['street'];
        if (array_key_exists('subdivision', $data)) $personal_info->subdivision = $data['subdivision'];
        if (array_key_exists('barangay', $data)) $personal_info->barangay = $data['barangay'];
        if (array_key_exists('city', $data)) $personal_info->city = $data['city'];
        if (array_key_exists('province', $data)) $personal_info->province = $data['province'];
        if (array_key_exists('zip_code', $data)) {
            if (!$is_new) {
                $this->logInfoChange($employee_id, 'personal_information', 'zip_code', $personal_info->zip_code, $data['zip_code']);
            }
            $personal_info->zip_code = $data['zip_code'];
        }

        if (array_key_exists('same_addresses', $data)) $personal_info->same_addresses = $data['same_addresses'];
        if (array_key_exists('p_house_number', $data)) $personal_info->p_house_number = $data['p_house_number'];
        if (array_key_exists('p_street', $data)) $personal_info->p_street = $data['p_street'];
        if (array_key_exists('p_subdivision', $data)) $personal_info->p_subdivision = $data['p_subdivision'];
        if (array_key_exists('p_barangay', $data)) $personal_info->p_barangay = $data['p_barangay'];
        if (array_key_exists('p_city', $data)) $personal_info->p_city = $data['p_city'];
        if (array_key_exists('p_province', $data)) $personal_info->p_province = $data['p_province'];
        if (array_key_exists('p_zip_code', $data)) $personal_info->p_zip_code = $data['p_zip_code'];

        if (array_key_exists('area_code', $data)) $personal_info->area_code = $data['area_code'];
        if (array_key_exists('telephone_number', $data)) $personal_info->telephone_number = $data['telephone_number'];
        if (array_key_exists('mobile_number', $data) && $data['mobile_number'] != null  && is_array($data['mobile_number'])) {
            if (!$is_new) {
                $this->logInfoChange(
                    $employee_id,
                    'personal_information',
                    'mobile_number',
                    $personal_info->mobile_number ? implode(",", $personal_info->mobile_number) : '',
                    implode(",", $data['mobile_number'])
                );
            }
            $personal_info->mobile_number = $data['mobile_number'];
        }
        if (array_key_exists('email_address', $data)) {
            if (!$is_new) {
                $this->logInfoChange(
                    $employee_id,
                    'personal_information',
                    'email_address',
                    $personal_info->email_address ? $personal_info->email_address : '',
                    $data['email_address']
                );
            }
            $personal_info->email_address = $data['email_address'];
        }

        $personal_info->save();

        return $personal_info->id;
    }

    public function update_personal_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $personal_information = \App\PersonalInformation::where('employee_id', '=', $employee->id)->first();
        if (!$personal_information) {
            throw new \Exception('Employee personal information not found.');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'middle_name' => 'required',
            'last_name' => 'required',
            'date_of_birth' => 'required',
            'place_of_birth' => 'required',
            'civil_status' => 'required',
            'gender' => 'required',
            'citizenship' => 'required',
            'dual_citizenship' => 'required|boolean',
            'country' => 'required_if:dual_citizenship,true',
            'by' => 'required_if:dual_citizenship,true'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            if ($request->input('first_name') !== $personal_information->first_name) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'first_name',
                    $personal_information->first_name,
                    $request->input('first_name')
                );
                $personal_information->first_name = $request->input('first_name');
            }

            if ($request->input('middle_name') !== $personal_information->middle_name) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'middle_name',
                    $personal_information->middle_name,
                    $request->input('middle_name')
                );
                $personal_information->middle_name = $request->input('middle_name');
            }

            if ($request->input('last_name') !== $personal_information->last_name) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'last_name',
                    $personal_information->last_name,
                    $request->input('last_name')
                );
                $personal_information->last_name = $request->input('last_name');
            }

            if ($request->input('name_extension') !== $personal_information->name_extension) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'name_extension',
                    $personal_information->name_extension,
                    $request->input('name_extension')
                );
                $personal_information->name_extension = $request->input('name_extension');
            }

            if ($request->input('date_of_birth') !== $personal_information->date_of_birth) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'date_of_birth',
                    $personal_information->date_of_birth,
                    $request->input('date_of_birth')
                );
                $personal_information->date_of_birth = $request->input('date_of_birth');
            }

            if ($request->input('place_of_birth') !== $personal_information->place_of_birth) {
                $this->logInfoChange(
                    $employee->id,
                    'personl_information',
                    'place_of_birth',
                    $personal_information->place_of_birth,
                    $request->input('place_of_birth')
                );
                $personal_information->place_of_birth = $request->input('place_of_birth');
            }

            if ($request->input('civil_status') !== $personal_information->civil_status) {
                $this->logInfoChange(
                    $employee->id,
                    'personl_information',
                    'civil_status',
                    $personal_information->civil_status,
                    $request->input('civil_status')
                );
                $personal_information->civil_status = $request->input('civil_status');
            }

            if ($request->input('height') !== $personal_information->height) {
                $personal_information->height = $request->input('height');
            }
            if ($request->input('weight') !== $personal_information->weight) {
                $personal_information->weight = $request->input('weight');
            }
            if ($request->input('blood_type') !== $personal_information->blood_type) {
                $personal_information->blood_type = $request->input('blood_type');
            }
            if ($request->input('gender') !== $personal_information->gender) {
                $personal_information->gender = $request->input('gender');
            }

            if ($request->input('citizenship') !== $personal_information->citizenship) {
                $personal_information->citizenship = $request->input('citizenship');
            }
            if ($request->input('dual_citizenship') !== $personal_information->dual_citizenship) {
                $personal_information->dual_citizenship = $request->input('dual_citizenship');
            }
            if ($request->input('country') !== $personal_information->country) {
                $personal_information->country = $request->input('country');
            }
            if ($request->input('by') !== $personal_information->by) {
                $personal_information->by = $request->input('by');
            }
            $personal_information->save();
            \DB::commit();
            return response()->json(array("data" => $personal_information, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_personal_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json($employee->personal_information);
    }

    public function update_location(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $personal_information = \App\PersonalInformation::where('employee_id', '=', $employee->id)->first();
        if (!$personal_information) {
            throw new \Exception('Employee personal information not found.');
        }

        $validator = Validator::make($request->all(), [
            'barangay' => 'required',
            'city' => 'required',
            'province' => 'required',
            'zip_code' => 'required',
            'same_addresses' => 'required',
            'p_barangay' => 'required_if:same_addresses,false',
            'p_city' => 'required_if:same_addresses,false',
            'p_province' => 'required_if:same_addresses,false',
            'p_zip_code' => 'required_if:same_addresses,false'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            if ($request->input('house_number') !== $personal_information->house_number) {
                $personal_information->house_number = $request->input('house_number');
            }
            if ($request->input('street') !== $personal_information->street) {
                $personal_information->street = $request->input('street');
            }
            if ($request->input('subdivision') !== $personal_information->subdivision) {
                $personal_information->subdivision = $request->input('subdivision');
            }
            if ($request->input('barangay') !== $personal_information->barangay) {
                $personal_information->barangay = $request->input('barangay');
            }
            if ($request->input('city') !== $personal_information->city) {
                $personal_information->city = $request->input('city');
            }
            if ($request->input('province') !== $personal_information->province) {
                $personal_information->province = $request->input('province');
            }
            if ($request->input('zip_code') !== $personal_information->zip_code) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'zip_code',
                    $personal_information->zip_code,
                    $request->input('zip_code')
                );
                $personal_information->zip_code = $request->input('zip_code');
            }

            if ($request->input('same_addresses') !== $personal_information->same_addresses) {
                $personal_information->same_addresses = $request->input('same_addresses');
            }

            $new_data = $personal_information->same_addresses ? $request->input('house_number') : $request->input('p_house_number');
            if ($new_data !== $personal_information->p_house_number) {
                $personal_information->p_house_number = $new_data;
            }
            $new_data = $personal_information->same_addresses ? $request->input('street') : $request->input('p_street');
            if ($new_data !== $personal_information->p_street) {
                $personal_information->p_street = $new_data;
            }
            $new_data = $personal_information->same_addresses ? $request->input('subdivision') : $request->input('p_subdivision');
            if ($new_data !== $personal_information->p_subdivision) {
                $personal_information->p_subdivision = $new_data;
            }
            $new_data = $personal_information->same_addresses ? $request->input('barangay') : $request->input('p_barangay');
            if ($new_data !== $personal_information->p_barangay) {
                $personal_information->p_barangay = $new_data;
            }
            $new_data = $personal_information->same_addresses ? $request->input('city') : $request->input('p_city');
            if ($new_data !== $personal_information->p_city) {
                $personal_information->p_city = $new_data;
            }
            $new_data = $personal_information->same_addresses ? $request->input('province') : $request->input('p_province');
            if ($new_data !== $personal_information->p_province) {
                $personal_information->p_province = $new_data;
            }
            $new_data = $personal_information->same_addresses ? $request->input('zip_code') : $request->input('p_zip_code');
            if ($new_data !== $personal_information->p_zip_code) {
                $personal_information->p_zip_code = $new_data;
            }

            $personal_information->save();

            \DB::commit();
            return response()->json(array("data" => $personal_information, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_location(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $result = \DB::table('personal_information')
            ->where('employee_id', '=', $employee->id)
            ->select(
                'id',
                'house_number',
                'street',
                'subdivision',
                'barangay',
                'city',
                'province',
                'zip_code',
                'same_addresses',
                'p_house_number',
                'p_street',
                'p_subdivision',
                'p_barangay',
                'p_city',
                'p_province',
                'p_zip_code',
                'created_at',
                'updated_at'
            )
            ->first();

        return response()->json($result);
    }

    public function update_contact_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $personal_information = \App\PersonalInformation::where('employee_id', '=', $employee->id)->first();
        if (!$personal_information) {
            throw new \Exception('Employee personal information not found.');
        }

        $validator = Validator::make($request->all(), [
            'mobile_number' => 'sometimes|array'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            if ($request->input('area_code') !== $personal_information->area_code) {
                $personal_information->area_code = $request->input('area_code');
            }
            if ($request->input('telephone_number') !== $personal_information->telephone_number) {
                $personal_information->telephone_number = $request->input('telephone_number');
            }
            if ($request->input('mobile_number') !== $personal_information->mobile_number) {
                $mobile_numbers_old = "[" . implode(",", $personal_information->mobile_number ?? []) . "]";
                $mobile_numbers_new = "[" . implode(",", $request->input('mobile_number')) . "]";
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'mobile_number',
                    $mobile_numbers_old,
                    $mobile_numbers_new
                );
                $personal_information->mobile_number = $request->input('mobile_number');
            }
            if ($request->input('email_address') !== $personal_information->email_address) {
                $this->logInfoChange(
                    $employee->id,
                    'personal_information',
                    'email_address',
                    $personal_information->email_address ?? '',
                    $request->input('email_address')
                );
                $personal_information->email_address = $request->input('email_address');
            }

            $personal_information->save();

            \DB::commit();
            return response()->json(array("data" => $personal_information, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_contact_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json($employee->personal_information->only(
            'id',
            'area_code',
            'telephone_number',
            'mobile_number',
            'email_address',
            'created_at',
            'updated_at'
        ));
    }

    public function add_family_background($data, $is_save_only, $is_new, $employee_id, $employee_type)
    {
        if (!$is_save_only && !in_array($employee_type, [2, 5])) {
            $validator = Validator::make($data, [
                'mother_last_name' => 'required',
                'mother_first_name' => 'required',
                'mother_middle_name' => 'required'
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        }

        if ($is_new) {
            $family_background = new \App\FamilyBackground();
            $family_background->employee_id = $employee_id;
        } else {
            $family_background = \App\FamilyBackground::where('employee_id', '=', $employee_id)->first();
            if(!$family_background){
                $family_background = new \App\FamilyBackground();
                $family_background->employee_id = $employee_id;
            }
        }

        if (array_key_exists('spouse_last_name', $data)) $family_background->spouse_last_name = $data['spouse_last_name'];
        if (array_key_exists('spouse_first_name', $data)) $family_background->spouse_first_name = $data['spouse_first_name'];
        if (array_key_exists('spouse_middle_name', $data)) $family_background->spouse_middle_name = $data['spouse_middle_name'];
        if (array_key_exists('name_extension', $data)) $family_background->name_extension = $data['name_extension'];
        if (array_key_exists('occupation', $data)) $family_background->occupation = $data['occupation'];
        if (array_key_exists('employer_name', $data)) $family_background->employer_name = $data['employer_name'];
        if (array_key_exists('business_address', $data)) $family_background->business_address = $data['business_address'];
        if (array_key_exists('telephone_number', $data)) $family_background->telephone_number = $data['telephone_number'];
        if (array_key_exists('children', $data)) $family_background->children = $data['children'];
        if (array_key_exists('father_last_name', $data)) $family_background->father_last_name = $data['father_last_name'];
        if (array_key_exists('father_first_name', $data)) $family_background->father_first_name = $data['father_first_name'];
        if (array_key_exists('father_middle_name', $data)) $family_background->father_middle_name = $data['father_middle_name'];
        if (array_key_exists('father_extension', $data)) $family_background->father_extension = $data['father_extension'];
        if (array_key_exists('mother_last_name', $data)) $family_background->mother_last_name = $data['mother_last_name'];
        if (array_key_exists('mother_first_name', $data)) $family_background->mother_first_name = $data['mother_first_name'];
        if (array_key_exists('mother_middle_name', $data)) $family_background->mother_middle_name = $data['mother_middle_name'];

        $family_background->save();

        return $family_background->id;
    }

    public function update_family_background(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $family_background = \App\FamilyBackground::where('employee_id', '=', $employee->id)->first();
        if (!$family_background) {
            $family_background = new \App\FamilyBackground();
            $family_background->employee_id = $employee->id;
        }

        if (!in_array($employee->employment_and_compensation->employee_type_id, [2,5])) {
            $validator = Validator::make($request->all(), [
                'mother_last_name' => 'required',
                'mother_first_name' => 'required',
                'mother_middle_name' => 'required',
                'children' => 'sometimes|array'
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        }

        \DB::beginTransaction();
        try {
            if ($request->filled('spouse_last_name')) $family_background->spouse_last_name = $request->input('spouse_last_name');
            if ($request->filled('spouse_first_name')) $family_background->spouse_first_name = $request->input('spouse_first_name');
            if ($request->filled('spouse_middle_name')) $family_background->spouse_middle_name = $request->input('spouse_middle_name');
            if ($request->filled('name_extension')) $family_background->name_extension = $request->input('name_extension');
            if ($request->filled('occupation')) $family_background->occupation = $request->input('occupation');
            if ($request->filled('employer_name')) $family_background->employer_name = $request->input('employer_name');
            if ($request->filled('business_address')) $family_background->business_address = $request->input('business_address');
            if ($request->filled('telephone_number')) $family_background->telephone_number = $request->input('telephone_number');
            if ($request->filled('children')) $family_background->children = $request->input('children');
            if ($request->filled('father_last_name')) $family_background->father_last_name = $request->input('father_last_name');
            if ($request->filled('father_first_name')) $family_background->father_first_name = $request->input('father_first_name');
            if ($request->filled('father_middle_name')) $family_background->father_middle_name = $request->input('father_middle_name');
            if ($request->filled('father_extension')) $family_background->father_extension = $request->input('father_extension');
            if ($request->filled('mother_last_name')) $family_background->mother_last_name = $request->input('mother_last_name');
            if ($request->filled('mother_first_name')) $family_background->mother_first_name = $request->input('mother_first_name');
            if ($request->filled('mother_middle_name')) $family_background->mother_middle_name = $request->input('mother_middle_name');
            $family_background->save();
            \DB::commit();
            return response()->json(array("data" => $family_background, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_family_background(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $result = \App\FamilyBackground::where('employee_id', '=', $employee->id)->first();

        return response()->json($result);
    }

    public function add_cases($cases, $is_save_only, $is_new, $employee)
    {
        $validator = Validator::make($cases, [
            '*.title' => 'required',
            '*.date_filed' => 'required|date',
            '*.id' => 'sometimes|exists:employee_cases',
            '*.status' => 'required',
            'deleted' => 'sometimes|boolean',
            'attachments.*.file_location' => 'required',
            'attachments.*.file_type' => 'required',
            'attachments.*.file_name' => 'required',
            'attachments.*.uid' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        foreach ($cases as $case) {
            if (array_key_exists('id', $case)) {
                $employee_case = \App\EmployeeCase::find($case['id']);
                if (array_key_exists('id', $case) && isset($case['deleted']) ) {
                    $employee_case->delete();
                    continue;
                }
                $employee_case->fill($case);
                $employee_case->save();
            }else {
                $employee_case = \App\EmployeeCase::create($case);
                $employee_case->employee_id = $employee->id;
                $employee_case->save();
            }

            $attachments = array_key_exists('documents', $case) ? $case['documents'] : [];
            foreach ($attachments as $attachment) {
                if (array_key_exists('id', $attachment)) {
                    $document = \App\Document::find($attachment['id']);
                    if (array_key_exists('id', $attachment) && isset($attachment['deleted']) ) {
                        $document->delete();
                        continue;
                    }
                    $document->fill($attachment);
                } else {
                    $document = \App\Document::create($attachment);
                }
                $document->employee_case_id = $employee_case->id;
                $document->save();
            }
            $employee_case->loadMissing('documents');
        }
    }

    public function add_employee_case(Request $request, $employee_id)
    {
        $validator_arr = [
            'title' => 'required',
            'date_filed' => 'required|date',
            'status' => 'required',
            'status_effective_date' => 'sometimes|date',
            'attachments.*.file_location' => 'required',
            'attachments.*.file_type' => 'required',
            'attachments.*.file_name' => 'required',
            'attachments.*.uid' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $employee_case = \App\EmployeeCase::create($request->all());
        $employee_case->employee_id = $employee_id;
        $employee_case->save();

        $attachments = $request->input('attachments', []);
        foreach ($attachments as $attachment) {
            if (array_key_exists('id', $attachment)) {
                $document = \App\Document::find($attachment['id']);
                if (array_key_exists('id', $attachment) && isset($attachment['deleted']) ) {
                    $document->delete();
                    continue;
                }
                $document->fill($attachment);
            } else {
                $document = \App\Document::create($attachment);
            }
            $document->employee_case_id = $employee_case->id;
            $document->save();
        }

        return response()->json(['result' => 'success', 'data' => $employee_case]);
    }

    public function update_employee_case(Request $request, \App\EmployeeCase $employee_case)
    {
        $validator_arr = [
            'title' => 'required',
            'date_filed' => 'required|date',
            'type' => 'required',
            'status' => 'required',
            'status_effective_date' => 'sometimes|date',
            'attachments.*.file_location' => 'required',
            'attachments.*.file_type' => 'required',
            'attachments.*.file_name' => 'required',
            'attachments.*.uid' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $employee_case->fill($request->all());
        $employee_case->save();

        $attachments = $request->input('attachments', []);
        foreach ($attachments as $attachment) {
            if (array_key_exists('id', $attachment)) {
                $document = \App\Document::find($attachment['id']);
                if (array_key_exists('id', $attachment) && isset($attachment['deleted']) ) {
                    $document->delete();
                    continue;
                }
                $document->fill($attachment);
            } else {
                $document = \App\Document::create($attachment);
            }
            $document->employee_case_id = $employee_case->id;
            $document->save();
        }

        $employee_case->load('documents');
        return response()->json(['result' => 'success', 'data' => $employee_case]);
    }

    public function delete_employee_case(Request $request, \App\EmployeeCase $employee_case)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $employee_case->delete();
        return response()->json(['result' => 'success']);
    }

    public function view_employee_cases(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\EmployeeCase::with('documents')
            ->where('employee_id', '=', $employee->id);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function add_attachments($attachments, $is_save_only, $is_new, $employee)
    {
        if (array_key_exists('profile_photo', $attachments)) {
            $profile_photo = $attachments['profile_photo'];
            if (isset($profile_photo['file_location']) && isset($profile_photo['file_name']) && isset($profile_photo['file_type'])) {
                $this->add_profile($profile_photo, $is_save_only, $is_new, $employee);
            }
        }

        $data = array_key_exists('files', $attachments) ? $attachments['files'] : [];
        if (!$is_new) {
            $documents = \App\Document::where('employee_id', $employee->id)
            ->whereNull('employee_case_id')
            ->delete();
        }

        foreach ($data as $file) {
            $document = new \App\Document();
            $document->employee_id = $employee->id;
            $document->file_location = $file['file_location'];
            $document->file_type = $file['file_type'];
            $document->file_name = $file['file_name'];
            $document->file_remarks = $file['file_remarks'];
            if (array_key_exists('file_date', $file) && $file['file_date'] != null) {
                $document->file_date = Carbon::parse($file['file_date'])->toDateString();
            }
            $document->save();
        }
    }

    public function add_profile($data, $is_save_only, $is_new, $employee)
    {
        if (!$is_new) {
            $profile = \App\ProfilePicture::where('employee_id', $employee->id)->delete();
        }

        $document = new \App\ProfilePicture();
        $document->employee_id = $employee->id;
        $document->file_location = $data['file_location'];
        $document->file_type =   $data['file_type'];
        $document->file_name =  $data['file_name'];
        $document->save();
    }

    public function update_profile_picture(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator = Validator::make($request->all(), [
            'file_location' => 'required|string',
            'file_name' => 'required|string',
            'file_type' => 'required|string'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $profile_picture = \App\ProfilePicture::where('employee_id', '=', $employee->id)->first();
        if (!$profile_picture) {
            $profile_picture = \App\ProfilePicture::create([
                'employee_id' => $employee->id,
                'file_location' => $request->input('file_location'),
                'file_name' => $request->input('file_name'),
                'file_type' => $request->input('file_type')
            ]);
        } else {
            $profile_picture->file_location = $request->input('file_location');
            $profile_picture->file_name = $request->input('file_name');
            $profile_picture->file_type = $request->input('file_type');
        }
        $profile_picture->save();

        return response()->json(array("data" => $profile_picture, "result" => "updated"));
    }

    public function update_profile(Request $request, $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $data  = $request->only('attachments')['attachments']['profile_photo'];

        $query = \App\ProfilePicture::where('employee_id', '=', $employee)->first();

        if ($query !== null) {
            $table = \App\ProfilePicture::where('employee_id', $employee)
                ->update(array(
                    "employee_id" => $employee,
                    "file_location" => $data['file_location'],
                    "file_name" =>  $data['file_name'],
                    "file_type" =>  $data['file_type']
                ));
        } else {
            $table = new \App\ProfilePicture();
            $table->employee_id = $employee;
            $table->file_location = $data['file_location'];
            $table->file_name = $data['file_name'];
            $table->file_type = $data['file_type'];
            $table->save();
        }

        $response = \App\ProfilePicture::where('employee_id', $employee)->get();

        return response()->json(array("data" => $response, "result" => "updated"));
    }

    public function add_educational_background($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            \App\EducationalBackground::where('employee_id', $employee_id)->delete();
        }
        foreach ($data as $data_item) {
            $educational_background = new \App\EducationalBackground();
            $educational_background->employee_id = $employee_id;
            if (array_key_exists('type', $data_item)) $educational_background->type = $data_item['type'];
            if (array_key_exists('school_name', $data_item)) $educational_background->school_name = $data_item['school_name'];
            if (array_key_exists('course', $data_item)) $educational_background->course = $data_item['course'];
            if (array_key_exists('start_year', $data_item)) $educational_background->start_year = $data_item['start_year'] ? $data_item['start_year'] : 0;
            if (array_key_exists('start_month', $data_item)) $educational_background->start_month = $data_item['start_month'] ? $data_item['start_month'] : 0;
            if (array_key_exists('start_day', $data_item)) $educational_background->start_day = $data_item['start_day'] ? $data_item['start_day'] : 0;
            if (array_key_exists('end_year', $data_item)) $educational_background->end_year = $data_item['end_year'] ? $data_item['end_year'] : 0;
            if (array_key_exists('end_month', $data_item)) $educational_background->end_month = $data_item['end_month'] ? $data_item['end_month'] : 0;
            if (array_key_exists('end_day', $data_item)) $educational_background->end_day = $data_item['end_day'] ? $data_item['end_day'] : 0;
            if (array_key_exists('highest_level', $data_item)) $educational_background->highest_level = $data_item['highest_level'];
            if (array_key_exists('units_earned', $data_item)) $educational_background->units_earned = $data_item['units_earned'];
            if (array_key_exists('year_graduated', $data_item) && $data_item['year_graduated'] != null) $educational_background->year_graduated = Carbon::parse($data_item['year_graduated'])->toDateString();
            if (array_key_exists('honors', $data_item)) $educational_background->honors = $data_item['honors'];
            $educational_background->save();
        }
    }

    public function update_educational_background(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $educational_backgrounds = \App\EducationalBackground::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|exists:educational_background,id',
            '*.type' => 'required|numeric',
            '*.school_name' => 'sometimes|nullable|string',
            '*.course' => 'sometimes|nullable|string',
            '*.honors' => 'sometimes|nullable|string',
            '*.highest_level' => 'sometimes|nullable|numeric',
            '*.units_earned' => 'sometimes|nullable|numeric',
            '*.start_year' => 'sometimes|nullable|numeric',
            '*.start_month' => 'sometimes|nullable|numeric',
            '*.start_day' => 'sometimes|nullable|numeric',
            '*.end_year' => 'sometimes|nullable|numeric',
            '*.end_month' => 'sometimes|nullable|numeric',
            '*.end_day' => 'sometimes|nullable|numeric',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $education) {
                $education = (object) $education;
                $background = null;
                if (property_exists($education, 'id')) {
                    $background = $educational_backgrounds->firstWhere('id', $education->id);
                }

                if (!$background) {
                    if (!(property_exists($education, 'deleted') && $education->deleted)) {
                        $background = \App\EducationalBackground::create((array) $education);
                        $background->employee_id = $employee->id;
                        $background->save();
                    }
                } else {
                    if (property_exists($education, 'deleted') && $education->deleted) {
                        $background->delete();
                    } else {
                        $background->fill((array) $education);
                        $background->save();
                    }
                }
            }
            $educational_backgrounds = \App\EducationalBackground::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $educational_backgrounds, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_educational_background(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $educational_background = \App\EducationalBackground::where('employee_id', $employee->id)->get();

        return response()->json($educational_background);
    }

    public function add_civil_service($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_civil_service = \App\CivilService::where('employee_id', $employee_id)->get();
            foreach ($old_civil_service as $item) {
                $item->delete();
            }
        }

        foreach ($data as $data_item) {
            $civil_service = new \App\CivilService();
            $civil_service->employee_id = $employee_id;
            if (array_key_exists('government_id', $data_item)) $civil_service->government_id = $data_item['government_id'];
            if (array_key_exists('date', $data_item) && $data_item['date'] != null) $civil_service->date = Carbon::parse($data_item['date'])->toDateString();
            if (array_key_exists('license_no', $data_item)) $civil_service->license_no = $data_item['license_no'];
            if (array_key_exists('validity_date', $data_item) && $data_item['validity_date'] != null) $civil_service->validity_date = Carbon::parse($data_item['validity_date'])->toDateString();
            if (array_key_exists('place', $data_item)) $civil_service->place = $data_item['place'];
            if (array_key_exists('place_rating', $data_item)) $civil_service->place_rating = $data_item['place_rating'];
            $civil_service->save();
        }
    }

    public function update_civil_service(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $civil_services = \App\CivilService::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|nullable|exists:civil_service,id',
            '*.validity_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.government_id' => 'sometimes|nullable|string',
            '*.license_no' => 'sometimes|nullable|string',
            '*.place' => 'sometimes|nullable|string',
            '*.place_rating' => 'sometimes|nullable|numeric',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $civil) {
                $civil = (object) $civil;
                $service = null;
                if (property_exists($civil, 'id')) {
                    $service = $civil_services->firstWhere('id', $civil->id);
                }

                if (!$service) {
                    if (!(property_exists($civil, 'deleted') && $civil->deleted)) {
                        $service = \App\CivilService::create((array) $civil);
                        $service->employee_id = $employee->id;
                        $service->save();
                    }
                } else {
                    if (property_exists($civil, 'deleted') && $civil->deleted) {
                        $service->delete();
                    } else {
                        $service->fill((array) $civil);
                        $service->save();
                    }
                }
            }
            $civil_services = \App\CivilService::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $civil_services, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_civil_service(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $civil_service =  \App\CivilService::where('employee_id', $employee->id)->get();
        return response()->json($civil_service);
    }

    public function add_work_experience($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_work_experience = \App\WorkExperience::where('employee_id', $employee_id)->get();
            foreach ($old_work_experience as $item) {
                $item->delete();
            }
        }

        foreach ($data as $data_item) {
            $work_experience = new \App\WorkExperience();
            $work_experience->employee_id = $employee_id;
            if (array_key_exists('start_inclusive_date', $data_item) && $data_item['start_inclusive_date'] != null) $work_experience->start_inclusive_date = Carbon::parse($data_item['start_inclusive_date'])->toDateString();
            if (array_key_exists('end_inclusive_date', $data_item) && $data_item['end_inclusive_date'] != null) $work_experience->end_inclusive_date = Carbon::parse($data_item['end_inclusive_date'])->toDateString();
            if (array_key_exists('position_title', $data_item)) $work_experience->position_title = $data_item['position_title'];
            if (array_key_exists('company', $data_item)) $work_experience->company = $data_item['company'];
            if (array_key_exists('monthly_salary', $data_item)) $work_experience->monthly_salary = $data_item['monthly_salary'];
            if (array_key_exists('pay_grade', $data_item)) $work_experience->pay_grade = $data_item['pay_grade'];
            if (array_key_exists('status_of_appointment', $data_item)) $work_experience->status_of_appointment = $data_item['status_of_appointment'];
            if (array_key_exists('government_service', $data_item)) $work_experience->government_service = $data_item['government_service'];

            $work_experience->save();
        }
    }

    public function update_work_experience(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $work_experiences = \App\WorkExperience::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|nullable|exists:work_experience,id',
            '*.start_inclusive_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.end_inclusive_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.company' => 'sometimes|nullable|string',
            '*.position_title' => 'sometimes|nullable|string',
            '*.monthly_salary' => 'sometimes|nullable|numeric',
            '*.status_of_appointment' => 'sometimes|nullable|string',
            '*.pay_grade' => 'sometimes|nullable|string',
            '*.government_service' => 'sometimes|nullable|boolean',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $work) {
                $work = (object) $work;
                $experience = null;
                if (property_exists($work, 'id')) {
                    $experience = $work_experiences->firstWhere('id', $work->id);
                }

                if (!$experience) {
                    if (!(property_exists($work, 'deleted') && $work->deleted)) {
                        $experience = \App\WorkExperience::create((array) $work);
                        $experience->employee_id = $employee->id;
                        $experience->save();
                    }
                } else {
                    if (property_exists($work, 'deleted') && $work->deleted) {
                        $experience->delete();
                    } else {
                        $experience->fill((array) $work);
                        $experience->save();
                    }
                }
            }
            $work_experiences = \App\WorkExperience::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $work_experiences, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_work_experience(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $work_experience = \App\WorkExperience::where('employee_id', $employee->id)->get();

        return response()->json($work_experience);
    }

    public function add_voluntary_work($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_voluntary_work = \App\VoluntaryWork::where('employee_id', $employee_id)->get();
            foreach ($old_voluntary_work as $item) {
                $item->delete();
            }
        }

        foreach ($data as $data_item) {
            $voluntary_work = new \App\VoluntaryWork();
            $voluntary_work->employee_id = $employee_id;
            if (array_key_exists('name_of_organization', $data_item)) $voluntary_work->name_of_organization = $data_item['name_of_organization'];
            if (array_key_exists('address', $data_item)) $voluntary_work->address = $data_item['address'];
            if (array_key_exists('start_inclusive_date', $data_item) && $data_item['start_inclusive_date'] != null) $voluntary_work->start_inclusive_date = Carbon::parse($data_item['start_inclusive_date'])->toDateString();
            if (array_key_exists('end_inclusive_date', $data_item) && $data_item['end_inclusive_date'] != null) $voluntary_work->end_inclusive_date = Carbon::parse($data_item['end_inclusive_date'])->toDateString();
            if (array_key_exists('number_of_hours', $data_item)) $voluntary_work->number_of_hours = $data_item['number_of_hours'];
            if (array_key_exists('position', $data_item)) $voluntary_work->position = $data_item['position'];

            $voluntary_work->save();
        }
    }

    public function update_voluntary_work(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $voluntary_works = \App\VoluntaryWork::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|nullable|exists:voluntary_work,id',
            '*.start_inclusive_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.end_inclusive_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.name_of_organization' => 'sometimes|nullable|string',
            '*.address' => 'sometimes|nullable|string',
            '*.number_of_hours' => 'sometimes|nullable|numeric',
            '*.position' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $work) {
                $work = (object) $work;
                $voluntary = null;
                if (property_exists($work, 'id')) {
                    $voluntary = $voluntary_works->firstWhere('id', $work->id);
                }

                if (!$voluntary) {
                    if (!(property_exists($work, 'deleted') && $work->deleted)) {
                        $voluntary = \App\VoluntaryWork::create((array) $work);
                        $voluntary->employee_id = $employee->id;
                        $voluntary->save();
                    }
                } else {
                    if (property_exists($work, 'deleted') && $work->deleted) {
                        $voluntary->delete();
                    } else {
                        $voluntary->fill((array) $work);
                        $voluntary->save();
                    }
                }
            }
            $voluntary_works = \App\VoluntaryWork::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $voluntary_works, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_voluntary_work(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $voluntary_work = \App\VoluntaryWork::where('employee_id', $employee->id)->get();

        return response()->json($voluntary_work);
    }

    public function add_training_program($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_training_program = \App\TrainingProgram::where('employee_id', $employee_id)->get();
            foreach ($old_training_program as $item) {
                $item->delete();
            }
        }

        foreach ($data as $data_item) {
            $training_program = new \App\TrainingProgram();
            $training_program->employee_id = $employee_id;
            if (array_key_exists('title', $data_item)) $training_program->title = $data_item['title'];
            if (array_key_exists('start_inclusive_date', $data_item) && $data_item['start_inclusive_date'] != null) $training_program->start_inclusive_date = Carbon::parse($data_item['start_inclusive_date'])->toDateString();
            if (array_key_exists('end_inclusive_date', $data_item) && $data_item['end_inclusive_date'] != null) $training_program->end_inclusive_date = Carbon::parse($data_item['end_inclusive_date'])->toDateString();
            if (array_key_exists('number_of_hours', $data_item)) $training_program->number_of_hours = $data_item['number_of_hours'];
            if (array_key_exists('sponsor', $data_item)) $training_program->sponsor = $data_item['sponsor'];
            if (array_key_exists('type', $data_item)) $training_program->type = $data_item['type'];

            $training_program->save();
        }
    }

    public function update_training_program(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $training_programs = \App\TrainingProgram::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|nullable|exists:training_program,id',
            '*.start_inclusive_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.end_inclusive_date' => 'sometimes|nullable|date_format:Y-m-d',
            '*.title' => 'sometimes|nullable|string',
            '*.type' => 'sometimes|nullable|string',
            '*.number_of_hours' => 'sometimes|nullable|numeric',
            '*.sponsor' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $training) {
                $training = (object) $training;
                $program = null;
                if (property_exists($training, 'id')) {
                    $program = $training_programs->firstWhere('id', $training->id);
                }

                if (!$program) {
                    if (!(property_exists($training, 'deleted') && $training->deleted)) {
                        $program = \App\TrainingProgram::create((array) $training);
                        $program->employee_id = $employee->id;
                        $program->save();
                    }
                } else {
                    if (property_exists($training, 'deleted') && $training->deleted) {
                        $program->delete();
                    } else {
                        $program->fill((array) $training);
                        $program->save();
                    }
                }
            }
            $training_programs = \App\TrainingProgram::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $training_programs, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_training_program(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $training_program = \App\TrainingProgram::where('employee_id', $employee->id)->get();

        return response()->json($training_program);
    }

    public function add_other_information($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_other_information = \App\OtherInformation::where('employee_id', $employee_id)->get();
            foreach ($old_other_information as $item) {
                $item->delete();
            }
        }

        foreach ($data as $data_item) {
            if (empty($data_item)) {
                continue;
            }

            if ($is_save_only) {
                $validator_arr = [];
            } else {
                $validator_arr = [
                    // 'special_skills' => 'required',
                    // 'recognition' => 'required',
                    // 'organization' => 'required'
                ];
            }

            $validator = Validator::make($data_item, $validator_arr);

            if ($validator->fails()) {
                return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
            }

            $other_information = new \App\OtherInformation();
            $other_information->employee_id = $employee_id;
            if (array_key_exists('special_skills', $data_item)) $other_information->special_skills = $data_item['special_skills'];
            if (array_key_exists('recognition', $data_item)) $other_information->recognition = $data_item['recognition'];
            if (array_key_exists('organization', $data_item)) $other_information->organization = $data_item['organization'];

            $other_information->save();
        }
    }

    public function update_other_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $other_informations = \App\OtherInformation::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|exists:other_information,id',
            '*.special_skills' => 'sometimes|nullable|string',
            '*.recognition' => 'sometimes|nullable|string',
            '*.organization' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $other) {
                $other = (object) $other;
                $information = null;
                if (property_exists($other, 'id')) {
                    $information = $other_informations->firstWhere('id', $other->id);
                }

                if (!$information) {
                    if (!(property_exists($other, 'deleted') && $other->deleted)) {
                        $information = \App\OtherInformation::create((array) $other);
                        $information->employee_id = $employee->id;
                        $information->save();
                    }
                } else {
                    if (property_exists($other, 'deleted') && $other->deleted) {
                        $information->delete();
                    } else {
                        $information->fill((array) $other);
                        $information->save();
                    }
                }
            }
            $other_informations = \App\OtherInformation::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $other_informations, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_other_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $other_information = \App\OtherInformation::where('employee_id', $employee->id)->get();

        return response()->json($other_information);
    }

    public function add_government_id($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_govt_id = \App\GovernmentId::where('employee_id', $employee_id)->get();
            foreach ($old_govt_id as $item) {
                $item->delete();
            }
        }


        if ($is_save_only) {
            $validator_arr = [];
        } else {
            $validator_arr = [
                // 'id_type' => 'required',
                // 'id_no' => 'required',
                // 'place_of_issue' => 'required',
                // 'date_of_issue=>'required'
            ];
        }

        $validator = Validator::make($data, $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $govt_id = new \App\GovernmentId();
        $govt_id->employee_id = $employee_id;
        if (array_key_exists('id_type', $data)) $govt_id->id_type = $data['id_type'];
        if (array_key_exists('id_no', $data)) $govt_id->id_no = $data['id_no'];
        if (array_key_exists('place_of_issue', $data)) $govt_id->place_of_issue = $data['place_of_issue'];
        if (array_key_exists('date_of_issue', $data)) $govt_id->date_of_issue = $data['date_of_issue'];

        $govt_id->save();
    }

    public function update_government_id(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $government_ids = \App\GovernmentId::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|nullable|exists:government_ids,id',
            '*.date_of_issue' => 'sometimes|nullable|date_format:Y-m-d',
            '*.id_no' => 'sometimes|nullable|string',
            '*.title' => 'sometimes|nullable|string',
            '*.place_of_issue' => 'sometimes|nullable|string',
            '*.id_type' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $id) {
                $id = (object) $id;
                $government = null;
                if (property_exists($id, 'id')) {
                    $government = $government_ids->firstWhere('id', $id->id);
                }

                if (!$government) {
                    if (!(property_exists($id, 'deleted') && $id->deleted)) {
                        $government = \App\GovernmentId::create((array) $id);
                        $government->employee_id = $employee->id;
                        $government->save();
                    }
                } else {
                    if (property_exists($id, 'deleted') && $id->deleted) {
                        $government->delete();
                    } else {
                        $government->fill((array) $id);
                        $government->save();
                    }
                }
            }
            $government_ids = \App\GovernmentId::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $government_ids, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_government_id(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query =  \App\GovernmentId::where('employee_id', '=', $employee->id)->get();

        return response()->json($query);
    }

    public function add_references($data, $is_save_only, $is_new, $employee_id)
    {
        if (!$is_new) {
            $old_reference = \App\Reference::where('employee_id', $employee_id)->get();
            foreach ($old_reference as $item) {
                $item->delete();
            }
        }
        foreach ($data as $data_item) {
            if (empty($data_item)) {
                continue;
            }

            if ($is_save_only) {
                $validator_arr = [];
            } else {
                $validator_arr = [
                    // 'special_skills' => 'required',
                    // 'recognition' => 'required',
                    // 'organization' => 'required'
                ];
            }

            $validator = Validator::make($data_item, $validator_arr);

            if ($validator->fails()) {
                return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
            }

            $reference = new \App\Reference();
            $reference->employee_id = $employee_id;
            if (array_key_exists('ref_name', $data_item)) $reference->ref_name = $data_item['ref_name'];
            if (array_key_exists('ref_address', $data_item)) $reference->ref_address = $data_item['ref_address'];
            if (array_key_exists('ref_tel_no', $data_item)) $reference->ref_tel_no = $data_item['ref_tel_no'];

            $reference->save();
        }
    }

    public function update_references(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $references = \App\Reference::where('employee_id', $employee->id)->get();

        $validator = Validator::make($request->all(), [
            '*.id' => 'sometimes|nullable|exists:references,id',
            '*.ref_name' => 'sometimes|nullable|string',
            '*.ref_address' => 'sometimes|nullable|string',
            '*.ref_tel_no' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            foreach ($request->all() as $requestReference) {
                $requestReference = (object) $requestReference;
                $referenceObject = null;
                if (property_exists($requestReference, 'id')) {
                    $referenceObject = $references->firstWhere('id', $requestReference->id);
                }

                if (!$referenceObject) {
                    if (!(property_exists($requestReference, 'deleted') && $requestReference->deleted)) {
                        $referenceObject = \App\Reference::create((array) $requestReference);
                        $referenceObject->employee_id = $employee->id;
                        $referenceObject->save();
                    }
                } else {
                    if (property_exists($requestReference, 'deleted') && $requestReference->deleted) {
                        $referenceObject->delete();
                    } else {
                        $referenceObject->fill((array) $requestReference);
                        $referenceObject->save();
                    }
                }
            }
            $references = \App\Reference::where('employee_id', $employee->id)->get();
            \DB::commit();
            return response()->json(array("data" => $references, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_references(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query =  \App\Reference::where('employee_id', '=', $employee->id)->get();

        return response()->json($query);
    }

    public function add_questionnaire($data, $is_save_only, $is_new, $employee_id)
    {
        if ($is_new) {
            $questionnaire = new \App\Questionnaire();
            $questionnaire->employee_id = $employee_id;
        } else {
            $questionnaire = \App\Questionnaire::where('employee_id', '=', $employee_id)->first();
        }

        if (array_key_exists('third_degree_relative', $data)) $questionnaire->third_degree_relative = $data['third_degree_relative'];
        if (array_key_exists('third_degree_relative_details', $data)) $questionnaire->third_degree_relative_details = $data['third_degree_relative_details'];
        if (array_key_exists('fourth_degree_relative', $data)) $questionnaire->fourth_degree_relative = $data['fourth_degree_relative'];
        if (array_key_exists('fourth_degree_relative_details', $data)) $questionnaire->fourth_degree_relative_details = $data['fourth_degree_relative_details'];
        if (array_key_exists('administrative_offender', $data)) $questionnaire->administrative_offender = $data['administrative_offender'];
        if (array_key_exists('administrative_offender_details', $data)) $questionnaire->administrative_offender_details = $data['administrative_offender_details'];
        if (array_key_exists('criminally_charged', $data)) $questionnaire->criminally_charged = $data['criminally_charged'];
        if (array_key_exists('criminally_charged_data', $data) && $data['criminally_charged_data'] != null && is_array($data['criminally_charged_data'])) $questionnaire->criminally_charged_data = $data['criminally_charged_data'];
        if (array_key_exists('convicted_of_crime', $data)) $questionnaire->convicted_of_crime = $data['convicted_of_crime'];
        if (array_key_exists('convicted_of_crime_details', $data)) $questionnaire->convicted_of_crime_details = $data['convicted_of_crime_details'];
        if (array_key_exists('separated_from_service', $data)) $questionnaire->separated_from_service = $data['separated_from_service'];
        if (array_key_exists('separated_from_service_details', $data)) $questionnaire->separated_from_service_details = $data['separated_from_service_details'];
        if (array_key_exists('election_candidate', $data)) $questionnaire->election_candidate = $data['election_candidate'];
        if (array_key_exists('election_candidate_details', $data)) $questionnaire->election_candidate_details = $data['election_candidate_details'];
        if (array_key_exists('resigned_from_gov', $data)) $questionnaire->resigned_from_gov = $data['resigned_from_gov'];
        if (array_key_exists('resigned_from_gov_details', $data)) $questionnaire->resigned_from_gov_details = $data['resigned_from_gov_details'];
        if (array_key_exists('multiple_residency', $data)) $questionnaire->multiple_residency = $data['multiple_residency'];
        if (array_key_exists('multiple_residency_country', $data)) $questionnaire->multiple_residency_country = $data['multiple_residency_country'];
        if (array_key_exists('indigenous', $data)) $questionnaire->indigenous = $data['indigenous'];
        if (array_key_exists('indigenous_group', $data)) $questionnaire->indigenous_group = $data['indigenous_group'];
        if (array_key_exists('pwd', $data)) $questionnaire->pwd = $data['pwd'];
        if (array_key_exists('pwd_id', $data)) $questionnaire->pwd_id = $data['pwd_id'];
        if (array_key_exists('solo_parent', $data)) $questionnaire->solo_parent = $data['solo_parent'];
        if (array_key_exists('solo_parent_id', $data)) $questionnaire->solo_parent_id = $data['solo_parent_id'];
        $questionnaire->save();
    }

    public function update_questionnaire(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $questionnaire = \App\Questionnaire::where('employee_id', $employee->id)->first();

        \DB::beginTransaction();
        try {
            if (!$questionnaire) {
                $questionnaire = \App\Questionnaire::create($request->all());
            } else {
                $questionnaire->fill($request->all());
            }
            $questionnaire->save();

            \DB::commit();
            return response()->json(array("data" => $questionnaire, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_questionnaire(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $questionnaire = \App\Questionnaire::where('employee_id', $employee->id)->first();

        return response()->json($questionnaire);
    }




    public function add_employment_and_compensation($data, $is_save_only, $is_new, \App\Employee $employee, $emptype)
    {
        if (!$is_save_only) {
            $exlucsionRule = 'exclude_unless:employee_type_id,1|exclude_unless:employee_type_id,6|required';
            $validator = Validator::make($data, [
                'position_id' => 'required',
                'job_info_effectivity_date' => $exlucsionRule,
                'department_id' => 'required', //'division'
                'section_id' => 'required',
                'employee_type_id' => 'required',
                'date_hired' => 'required',
                'salary_grade_id' => $exlucsionRule,
                'step_increment' => $exlucsionRule,
                'work_schedule_id' => 'required',
                'account_name' => 'required',
                'account_number' => 'required',
                // 'sss_number' => 'required',
                'pagibig_number' => 'required',
                'gsis_number' => 'required',
                'philhealth_number' => 'required',
                'tin' => 'required',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        }

        if ($is_new) {
            $employment_and_compensation = new \App\EmploymentAndCompensation();
        } else {
            $employment_and_compensation = \App\EmploymentAndCompensation::where('employee_id', '=', $employee->id)->first();
        }

        $employment_and_compensation->employee_id = $employee->id;
        if (array_key_exists('id_number', $data)) $employment_and_compensation->id_number = $data['id_number'];
        if (isset($data['position_id']) && !in_array($emptype, [2, 5])) {
            $position = \App\Position::find($data['position_id']);
            if (!$position) {
                throw new \Exception('Position selected does not exist!');
            }
            //check if new position is vaccant or not (only one person per position)
            if (!$is_new && $data['position_id'] === $employment_and_compensation->position_id) {
                //if editting and previous id is same as current id, continue
            } else if (!$position->vacancy) {
                throw new \Exception("Position selected is already filled.");
            }
            //unfill part -> remove filled mark from old position
            if ($employment_and_compensation->position_id !== NULL && $data['position_id'] !== $employment_and_compensation->position_id) {
                \DB::table('positions')->where('id', '=', $employment_and_compensation->position_id)->update(array('vacancy' => \App\Position::VACANCY_UNFILLED));
            }

            if (!$is_new) {
                $this->logInfoChange(
                    $employee->id,
                    'employment_and_compensation',
                    'position_id',
                    $employment_and_compensation->position_id,
                    $data['position_id']
                );
            }
            //set as new position
            $employment_and_compensation->position_id = $data['position_id'];
            $employment_and_compensation->position_name = null;
            $position->vacancy = \App\Position::VACANCY_FILLED;
            $position->save();
        } else if (array_key_exists('position_id', $data) && in_array($emptype, [2, 5])) {
            $employment_and_compensation->position_name = $data['position_id'];
            $employment_and_compensation->position_id = null;
        }
        if (array_key_exists('job_info_effectivity_date', $data) && $data['job_info_effectivity_date'] != null) $employment_and_compensation->job_info_effectivity_date = Carbon::parse($data['job_info_effectivity_date'])->toDateString();
        if (array_key_exists('work_sched_effectivity_date', $data) && $data['work_sched_effectivity_date'] != null) $employment_and_compensation->work_sched_effectivity_date = Carbon::parse($data['work_sched_effectivity_date'])->toDateString();
        if (array_key_exists('department_id', $data)) $employment_and_compensation->department_id = $data['department_id'];
        // if(array_key_exists('section', $data)) $employment_and_compensation->section = $data['section'];
        if (array_key_exists('employee_type_id', $data)) {
            if (!$is_new) {
                $this->logInfoChange(
                    $employee->id,
                    'employment_and_compensation',
                    'employee_type_id',
                    $employment_and_compensation->employee_type_id,
                    $data['employee_type_id']
                );
            }
            $employment_and_compensation->employee_type_id = $data['employee_type_id'];
        }
        if (array_key_exists('date_hired', $data) && $data['date_hired'] != null) $employment_and_compensation->date_hired = Carbon::parse($data['date_hired'])->toDateString();
        if (array_key_exists('salary_grade_id', $data)) $employment_and_compensation->salary_grade_id = $data['salary_grade_id'];
        if (array_key_exists('step_increment', $data)) $employment_and_compensation->step_increment = $data['step_increment'];
        if (array_key_exists('work_schedule_id', $data)) $employment_and_compensation->work_schedule_id = $data['work_schedule_id'];
        if (array_key_exists('account_name', $data)) $employment_and_compensation->account_name = $data['account_name'];
        if (array_key_exists('account_number', $data)) $employment_and_compensation->account_number = $data['account_number'];
        if (array_key_exists('sss_number', $data)) $employment_and_compensation->sss_number = $data['sss_number'];
        if (array_key_exists('pagibig_number', $data)) $employment_and_compensation->pagibig_number = $data['pagibig_number'];
        if (array_key_exists('gsis_number', $data)) $employment_and_compensation->gsis_number = $data['gsis_number'];
        if (array_key_exists('philhealth_number', $data)) $employment_and_compensation->philhealth_number = $data['philhealth_number'];
        if (array_key_exists('tin', $data)) $employment_and_compensation->tin = $data['tin'];
        if (array_key_exists('section_id', $data)) $employment_and_compensation->section_id = $data['section_id'];
        if (array_key_exists('direct_report_id', $data)) $employment_and_compensation->direct_report_id = $data['direct_report_id'];
        if (array_key_exists('employment_history', $data) && count($data['employment_history']) > 0) {
            $this->add_employment_history($data['employment_history'], $is_save_only, $is_new, $employee);
        }
        if (array_key_exists('salary_rate', $data)) $employment_and_compensation->salary_rate = $data['salary_rate'];
        if (array_key_exists('start_date', $data)) $employment_and_compensation->start_date = $data['start_date'];
        if (array_key_exists('period_of_service', $data)) {
            $employment_and_compensation->period_of_service_start = $data['period_of_service'][0];
            $employment_and_compensation->period_of_service_end = $data['period_of_service'][1];
        }
        $employment_and_compensation->save();
    }

    public function add_employment_history($data, $is_save_only, $is_new, $employee)
    {
        foreach ($data as $employment_history) {
            $employment_history = (object) $employment_history;
            if ($employment_history->id === -1) {
                $new_employment_history = \App\EmploymentHistory::create([
                    'employee_id' => $employee->id,
                    'position_id' => $employment_history->position_id,
                    'department_id' => $employment_history->department_id,
                    'start_date' => $employment_history->start_date,
                    'end_date' => $employment_history->end_date,
                ]);
            } else {
                $new_employment_history = \App\EmploymentHistory::find($employment_history->id);

                if (isset($employment_history->deleted) && $employment_history->deleted) {
                    $new_employment_history->delete();
                    continue;
                }

                $new_employment_history->employee_id = $employee->id;
                $new_employment_history->position_id = $employment_history->position_id;
                $new_employment_history->department_id = $employment_history->department_id;
                $new_employment_history->start_date = $employment_history->start_date;
                $new_employment_history->end_date = $employment_history->end_date;
            }

            if (isset($employment_history->status)) {
                $new_employment_history->status = $employment_history->status;
            }
            if (isset($employment_history->salary)) {
                $new_employment_history->salary = $employment_history->salary;
            }
            if (isset($employment_history->tranche_version)) {
                $new_employment_history->tranche_version = $employment_history->tranche_version;
            }
            if (isset($employment_history->branch)) {
                $new_employment_history->branch = $employment_history->branch;
            }
            if (isset($employment_history->lwop)) {
                $new_employment_history->lwop = $employment_history->lwop;
            }
            if (isset($employment_history->separation_date)) {
                $new_employment_history->separation_date = $employment_history->separation_date;
            }
            if (isset($employment_history->separation_cause)) {
                $new_employment_history->separation_cause = $employment_history->separation_cause;
            }
            if (isset($employment_history->separation_amount_received)) {
                $new_employment_history->separation_amount_received = $employment_history->separation_amount_received;
            }
            if (isset($employment_history->remarks)) {
                $new_employment_history->remarks = $employment_history->remarks;
            }
            $new_employment_history->save();
        }
    }

    public function view_employment_and_compensation(Request $request, $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\EmploymentAndCompensation::with([
            'employee_number',
            'department',
            'section',
            'position.department',
            'employee_type',
        ])
            ->where('employee_id', $employee)
            ->first();

        $reponse = array();
        $response['id_number'] = $query->id_number;
        $response['date_hired'] = $query->date_hired;
        $response['step_increment'] = $query->step_increment;

        // $response['position'] = \App\Position::with('department')
        //     ->where('id', '=', $query->position_id)
        //     ->select(array('id', 'position_name', 'salary_grade', 'is_active', 'item_number', 'department_id'))
        //     ->first();
        $response['position'] = $query->position;
        $response['job_info_effectivity_date'] = $query->job_info_effectivity_date;
        $response['work_sched_effectivity_date'] = $query->work_sched_effectivity_date;
        $response['department'] = $query->department;
        // $response['department'] = \DB::table('departments')
        //     ->where('id', '=', $query->department_id)
        //     ->select(array('id', 'department_name', 'code'))
        //     ->first();
        $response['section'] = $query->section;
        // $response['section'] = \DB::table('sections')
        //     ->where('id', '=', $query->section)
        //     ->select(array('id', 'section_name'))
        //     ->first();
        $response['employee_type'] = $query->employee_type;
        $response['employee_type_id'] = $query->employee_type_id;
        // $response['employee_type'] = \DB::table('employee_types')
        //     ->where('id', '=', $query->employee_type_id)
        //     ->select(array('id', 'employee_type_name'))
        //     ->first();
        $response['salary_grade'] = \DB::table('salaries')
            ->where('id', '=', $query->salary_grade_id)
            ->select(array('id', 'grade'))
            ->first();

        $schedule = \DB::table('work_schedules')
            ->where('id', '=', $query->work_schedule_id)
            ->first();

        $work_schedule = array();
        if ($schedule != null) {
            $work_schedule['id'] = $schedule->id;  // work_schedule_id
            $work_schedule['work_schedule_name'] = $schedule->work_schedule_name;
            $work_schedule['time_option'] = $schedule->time_option;

            if ($schedule->time_option == 1) {
                $schedule_query = \DB::table('fixed_daily_hours')
                    ->where('work_schedule_id', '=', $schedule->id)
                    ->get()
                    ->first();

                $daily_hours = json_decode($schedule_query->daily_hours, true);
                foreach ($daily_hours as $daily_hour => $details) {
                    $time_option_details[$daily_hour] = $details;
                }
                $work_schedule['time_option_details'] = $time_option_details;
            } else if ($schedule->time_option == 2) {
                $schedule_query = \DB::table('fixed_daily_times')
                    ->where('work_schedule_id', '=', $schedule->id)
                    ->get()
                    ->first();

                $start_times = json_decode($schedule_query->start_times, true);
                $end_times = json_decode($schedule_query->end_times, true);
                $grace_periods = json_decode($schedule_query->grace_periods, true);
                $keys = array_keys($grace_periods);
                foreach ($keys as $key => $value) {
                    $time_option_details[$value] = array();

                    $time_option_details[$value]['start_time'] = $start_times[$value];
                    $time_option_details[$value]['end_time'] = $end_times[$value];
                    $time_option_details[$value]['grace_period'] = $grace_periods[$value];
                }
                $work_schedule['time_option_details'] = $time_option_details;
            } else if ($schedule->time_option == 3) {
                $work_schedule['time_option_details'] = ['hours' => $schedule->flexible_weekly_hours];
            }

            $response['schedule'] = $work_schedule;
            $work_schedule['breaks'] = \DB::table('break_times')
                ->where('work_schedule_id', '=', $schedule->id)
                ->get();
        } else {
            $response['work_schedule'] = null;
        }

        $response['schedule'] = $work_schedule;
        if ($schedule != null) {
            $response['breaks'] = \DB::table('break_times')
                ->where('work_schedule_id', '=', $schedule->id)
                ->get();
        }
        $response['account_name'] = $query->account_name;
        $response['account_number'] = $query->account_number;
        $response['sss_number'] = $query->sss_number;
        $response['pagibig_number'] = $query->pagibig_number;
        $response['gsis_number'] = $query->gsis_number;
        $response['philhealth_number'] = $query->philhealth_number;
        $response['tin'] = $query->tin;
        $response['direct_report_id'] = $query->direct_report_id;
        $response['position_name'] = $query->position_name;
        $response['period_of_service_start'] = $query->period_of_service_start;
        $response['period_of_service_end'] = $query->period_of_service_end;
        $response['start_date'] = $query->start_date;
        $response['salary_rate'] = $query->salary_rate;

        // $work_experience = \App\WorkExperience::where('employee_id', $employee)
        // ->where('government_service', 1)
        // ->select(
        //     'start_inclusive_date as start_date',
        //     'end_inclusive_date as end_date',
        //     'position_title as position_name',
        //     'monthly_salary as salary',
        //     'company as department_name',
        //     'status_of_appointment as status'
        // )
        // ->orderBy('start_date', 'ASC')
        // ->get();

        $employee_history = \App\EmploymentHistory::with([
            'position' => function ($q) {
                $q->select('id', 'salary_grade', 'position_name', 'item_number');
            },
            'department' => function ($q) {
                $q->select('id', 'department_name', 'code');
            },
        ])
        ->where('employee_id', '=', $employee)
        ->get();
        $response['employee_history'] = $employee_history; // $work_experience->mergeRecursive($employee_history);

        return response()->json($response);
    }

    private function add_time_off_balance($data, $is_save, $is_new, $employee_id)
    {
        foreach ($data as $data_item) {
            $validator_arr = [
                'time_off_id' => 'required|exists:time_offs,id'
            ];

            $validator = Validator::make($data_item, $validator_arr);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            if(!$is_new && isset($data_item['points']) && isset($data_item['id'])){
                \App\TimeOffAdjustment::where('time_off_balance_id', $data_item['id'])->delete();
            }
            if (isset($data_item['deleted']) && $data_item['deleted']) {
                if (isset($data_item['id'])) {
                    \App\TimeOffBalance::destroy($data_item['id']);
                    \App\TimeOffAdjustment::where('time_off_balance_id', $data_item['id'])->delete();
                }
                continue;
            }

            $time_off_balance = \App\TimeOffBalance::firstOrNew(
                [
                    'employee_id' => $employee_id,
                    'time_off_id' => $data_item['time_off_id']
                ]
            );
            $time_off_balance->save();

            if(isset($data_item['points'])){
                $time_off_adjustment = \App\TimeOffAdjustment::create([
                    'time_off_balance_id' => $time_off_balance->id,
                    'adjustment_value' => $data_item['points'],
                    'effectivity_date' => Carbon::now()->toDateString(),
                    'remarks' => 'Initialized value'
                ]);
            }
        }
    }

    public function update_time_off_balances(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'time_off_balance' => 'required|array',
            'time_off_balance.*.time_off_id' => 'required|exists:time_offs,id',
            'time_off_balance.*.id' => 'sometimes|exists:time_off_balance,id',
            'time_off_balance.*.adjustment_value' => 'required|numeric',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            foreach ($request->input('time_off_balance') as $time_off_balance) {
                if (isset($time_off_balance['deleted']) && $time_off_balance['deleted']) {
                    if (isset($time_off_balance['id'])) {
                        $pending_time_offs = \App\TimeOffRequest::where('status', '!=', -1)
                            ->where('time_off_balance_id', $time_off_balance['id'])
                            ->count();
                        if ($pending_time_offs <= 0) {
                            \App\TimeOffBalance::destroy($time_off_balance['id']);
                            \App\TimeOffAdjustment::where('time_off_balance_id', $time_off_balance['id'])->delete();
                        } else {
                            return response()->json(['error' => 'Cannot delete.', 'message' => 'Time off is active.'], 400);
                        }
                    }
                    continue;
                }

                if (isset($time_off_balance['id'])) {
                    $adjustment = \App\TimeOffAdjustment::create([
                        'time_off_balance_id' => $time_off_balance['id'],
                        'adjustment_value' => $time_off_balance['adjustment_value'],
                        'effectivity_date' => Carbon::now()->toDateString(),
                    ]);
                } else {
                    $new_balance = \App\TimeOffBalance::firstOrNew(
                        [
                            'employee_id' => $employee_id,
                            'time_off_id' => $time_off_balance['time_off_id']
                        ]
                    );
                    $new_balance->save();
                    $adjustment = \App\TimeOffAdjustment::create([
                        'time_off_balance_id' => $new_balance->id,
                        'adjustment_value' => $time_off_balance['adjustment_value'],
                        'effectivity_date' => Carbon::now()->toDateString(),
                        'remarks' => "Modified by: " . $this->me->employee_details->id,
                    ]);
                }
            }

            $this->log_user_action(
                Carbon::parse($adjustment->created_at)->toDateString(),
                Carbon::parse($adjustment->created_at)->toTimeString(),
                $this->me->id,
                $this->me->name,
                "Adjusted Time Off Balances of Employee: " . $employee_id . ".",
                "HR & Payroll"
            );

            \DB::commit();
            $time_off_balance = \App\TimeOffBalance::with(['time_off.color', 'adjustments'])
                ->where('employee_id', $employee_id)
                ->get();

            return response()->json($time_off_balance);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_time_off_balance(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $response = \App\TimeOffBalance::with([
            'time_off.color',
            'requests' => function ($q) {
                $q->where('status', '!=', -1);
            },
            'requests.time_off_details' => function ($q) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
            },
            'adjustments' => function ($q) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
            },
        ])
            ->where('employee_id', $employee->id)
            ->get();

        return response()->json(array("data" => $response));
    }

    public function view_pending_time_off(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $pending_time_off_requests = \App\TimeOffRequest::where([
            ['employee_id', $employee->id],
            ['status', 0]
        ])
            ->get();

        return response()->json($pending_time_off_requests);
    }

    public function view_upcoming_time_off(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $upcoming_time_off_requests = \App\TimeOffRequest::where([
            ['employee_id', $employee->id],
            ['status', 1]
        ])
            ->whereHas('time_off_details', function ($q) {
                $q->where('time_off_date', '>=', Carbon::now()->toDateString());
            })
            ->get();

        return response()->json($upcoming_time_off_requests);
    }

    public function view_time_off_history(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $history_time_off_requests = \App\TimeOffRequest::where([
            ['employee_id', $employee->id],
            ['status', 1]
        ])
            ->whereHas('time_off_details', function ($q) {
                $q->where('time_off_date', '<', Carbon::now()->toDateString());
            })
            ->get();

        return response()->json($history_time_off_requests);
    }

    public function add_system_information($data, $is_save_only, $is_new, \App\Employee $employee)
    {
        if ($is_new) {
            $validator_arr = [
                'email' => 'required',
                'password' => 'required',
                'modules.*.client_id' => 'required',
                'modules.*.role' => 'required',
            ];
        } else {
            $validator_arr = [
                'email' => 'required'
            ];
        }

        $validator = Validator::make($data, $validator_arr);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        if (!$is_save_only) {
            $validator_arr = [
                'client_id' => 'required',
                'role' => 'required',
                'privileges' => 'required'
            ];

            foreach ($data['modules'] as $module) {
                $validator = Validator::make($module, $validator_arr);
                if ($validator->fails()) {
                    throw new \Exception($validator->errors()->first());
                }
            }
        }

        if ($is_new) {
            $system_info = new \App\SystemInformation();
        } else {
            $system_info = \App\SystemInformation::where('employee_id', '=', $employee->id)->first();
        }

        $system_info->email = $data['email'];
        if (!$is_new) {
            if (array_key_exists('password', $data) && $data['password'] != null) {
                $system_info->password = bcrypt($data['password']);
            }
        } else {
            $system_info->password = bcrypt($data['password']);
        }

        if (array_key_exists('modules', $data)) {
            $client_id = array();
            $role = array();
            $privileges = array();

            foreach ($data['modules'] as $module) {
                array_push($client_id, $module['client_id']);
                array_push($role, $module['role']);
                array_push($privileges, $module['privileges']);
            }

            $system_info->client_id = $client_id;
            $system_info->role = $role;
            $system_info->privileges = $privileges;
            $system_info->employee_id = $employee->id;
        }

        $system_info->save();
        return $system_info->id;
    }

    public function edit_system_info_privileges(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'modules' => 'required'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $validator_arr = [
            'client_id' => 'required',
            'role' => 'required',
            'privileges' => 'required'
        ];

        foreach ($request['modules'] as $module) {
            $validator = Validator::make($module, $validator_arr);

            if ($validator->fails()) {
                return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
            }
        }

        $system_info = \App\SystemInformation::where('employee_id', '=', $employee->id)->first();

        $client_id = array();
        $role = array();
        $privileges = array();

        foreach ($request['modules'] as $module) {
            array_push($client_id, $module['client_id']);
            array_push($role, $module['role']);
            array_push($privileges, $module['privileges']);
        }

        $system_info->client_id = $client_id;
        $system_info->role = $role;
        $system_info->privileges = $privileges;
        $system_info->employee_id = $employee->id;
        $system_info->save();

        $this->log_user_action(
            Carbon::now(),
            Carbon::now(),
            $this->me->id,
            $this->me->name,
            "Modified employee's privileges",
            "Employee & System Information"
        );

        return response()->json(
            array(
                "id" => $employee->id,
                "result" => "Updated privileges",
                "privileges" => $system_info
            )
        );
    }

    public function edit_system_info_email(Request $request, \App\Employee $employee)
    {
        $this->me = JWTAuth::parseToken()->authenticate();

        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }

        $system_info = \App\SystemInformation::where('employee_id', '=', $employee->id)->first();

        if ($request->exists('email')) $system_info->email = $request['email'];
        if ($request->exists('password')) $system_info->password = bcrypt($request['password']);
        $system_info->save();


        $this->log_user_action(
            Carbon::now(),
            Carbon::now(),
            $this->me->id,
            $this->me->name,
            "E-mail and password of " . $request['email'] . " changed",
            "Employee & Sytem Information"
        );

        return response()->json(array(
            "id" => $employee->id,
            "result" => "Updated e-mail and password",
            "data" => $system_info
        ));
    }

    public function view_system_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \DB::table('system_information')
            ->where('employee_id', '=', $employee->id)
            ->first();

        $response['email'] = $query->email;
        $modules = array();
        for ($i = 0; $i < count(json_decode($query->client_id, true)); $i++) {
            $module = array();
            $module['client_id'] = json_decode($query->client_id, true)[$i];
            $module['role'] = json_decode($query->role, true)[$i];
            $module['privileges'] = json_decode($query->privileges, true)[$i];
            array_push($modules, $module);
        }
        $response['modules'] = $modules;

        return response()->json($response);
    }

    public function list_view_system_information(Request $request,  \App\SystemInformation $employee)
    {
        $this->me = JWTAuth::parseToken()->authenticate();

        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }

        $query = $employee->select('*');
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $data = $response['data'];
        $adjustments = array();

        foreach ($data as $items) {
            $array = [];
            $array['email'] = $items->email;
            $array['client_id'] = $items->client_id;
            $array['role'] = $items->role;
            $array['privileges'] = $items->privileges;
            $array['created_at'] = $items->created_at;
            $array['updated_at'] = $items->updated_at;

            array_push($adjustments, $array);
        }

        $response['data'] = $adjustments;
        return response()->json($response);
    }

    public function auto_generated_id($result, $employee_id, $employment_and_compensation)
    {
        $string = "NPO";
        $last_id_number = \DB::table('employee_id_number')->latest()->first()->number;
        $increment = $last_id_number + 1;
        $count_number = strlen(json_encode($last_id_number));
        $entry_new_id = new \App\EmployeeIdNumber();
        $year =  $entry_new_id->year = Carbon::parse($employment_and_compensation['date_hired'])->format('Y');
        $month =  $entry_new_id->month = Carbon::parse($employment_and_compensation['date_hired'])->format('m');
        $entry_new_id->code = 'NPO';
        $entry_new_id->employee_id = $employee_id;
        $entry_new_id->year = Carbon::parse($employment_and_compensation['date_hired'])->format('Y');
        $entry_new_id->month = Carbon::parse($employment_and_compensation['date_hired'])->format('m');
        $entry_new_id->month = Carbon::parse($employment_and_compensation['date_hired'])->format('m');
        $entry_new_id->day = Carbon::parse($employment_and_compensation['date_hired'])->format('d');
        $entry_new_id->date = Carbon::parse($employment_and_compensation['date_hired'])->format('d-m-y');
        $entry_new_id->id_number = "NPO00000000000";
        $entry_new_id->number = $increment;
        $entry_new_id->save();

        $id_number_final = $this->numberIncementing($count_number, $increment);
        $final_id_number = $year . $month . $id_number_final;

        if ($entry_new_id->id) {
            \DB::table('employee_id_number')
                ->where('id', $entry_new_id->id)
                ->update(array(
                    'id_number' => $final_id_number
                ));
        }

        // TO DO for every year refresh to 001
    }

    public function numberIncementing($count, $increment)
    {
        $number;

        if ($count === 1) {
            $number =  '00' . $increment;
        } else if ($count === 3) {
            $number = $increment;
        } else {
            $number = '0' . $increment;
        }

        return $number;
    }

    public function create_user(Request $request, $employee, $is_save_only, $is_new)
    {
        $general_info = $request->only('general')['general']['personal_information'];
        $employment_and_compensation = $request->only('employment_and_compensation')['employment_and_compensation'];
        $system_info = $request->only('system_information')['system_information'];

        $client = new \GuzzleHttp\Client();

        if (!$is_save_only && !$is_new) {
            $password = \DB::table('system_information')->where('employee_id', $employee->id)->first()->password;
            $edit_activate_url = config('app.EDIT_ACTIVATE_USER_URL');
        } else {
            $password = $system_info['password'];
            $edit_activate_url = config('app.CREATE_USER_URL');
        }

        try {
            $res = $client->post($edit_activate_url, [\GuzzleHttp\RequestOptions::JSON => [
                'name' => $general_info['first_name'] . ' ' . $general_info['last_name'],
                'email' => $system_info['email'],
                'password' => $password,
                'permissions' => ['temporary' => true],
                'division' => $employment_and_compensation['department_id'],
                'section' => $employment_and_compensation['section_id']
            ]], ['http_errors' => false]);

            $result = (string) $res->getBody();
            $result = json_decode($result); //decode string into object

            if ($result) {
                $this->auto_generated_id($result, $employee->id, $employment_and_compensation);
            }

            return $result; //return object

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return (object) array(
                'error' => 'Something went wrong! Email must have been taken or password does not meet security requirements. Password must contain atleast 1 capital letter, 1 small letter, 1 number, and 1 special character.'
            );
        }
    }

    public function update_user(Request $request, $user_id)
    {

        //back end na lang magbigay!!!!!!!!!!!!!!!!!!!!!!
        $update_array = array();
        //default values (if hindi changed), these are required by UserController
        $query = \DB::table('employees')->where('users_id', $user_id)->first();
        //same name
        $update_array['first_name'] = \DB::table('personal_information')
            ->where('employee_id', '=', $query->id)->first()->first_name;
        $update_array['last_name'] = \DB::table('personal_information')
            ->where('employee_id', '=', $query->id)->first()->last_name;
        //same email
        $update_array['email'] = \DB::table('system_information')
            ->where('employee_id', '=', $query->id)->first()->email;
        //same permissions
        $update_array['permissions'] = ['temporary' => true];

        if ($request->filled('general')) {
            $general_info = $request->input('general');
            if (array_key_exists('personal_information', $general_info)) {
                $personal_info = $general_info['personal_information'];
                if (array_key_exists('first_name', $personal_info) && $personal_info['first_name'] != null) $update_array['first_name'] = $personal_info['first_name'];
                if (array_key_exists('last_name', $personal_info) && $personal_info['last_name'] != null) $update_array['last_name'] = $personal_info['last_name'];
            }
        }
        $update_array['name'] = $update_array['first_name'] . ' ' . $update_array['last_name'];

        if ($request->filled('employment_and_compensation')) { //if eac is in the request
            $employment_and_compensation = $request->only('employment_and_compensation')['employment_and_compensation'];
            if (array_key_exists('division', $employment_and_compensation)) $update_array['division'] = $employment_and_compensation['department_id'];
            if (array_key_exists('section_id', $employment_and_compensation)) $update_array['section'] = $employment_and_compensation['section_id'];
        }

        if ($request->filled('system_information')) { // if system information is in the request
            $system_info = $request->only('system_information')['system_information'];
            if (array_key_exists('email', $system_info)) $update_array['email'] = $system_info['email'];
            if (array_key_exists('password', $system_info) && $system_info['password'] != null) $update_array['password'] = $system_info['password'];
            $update_array['permissions'] = ['temporary' => true];
        }

        $client = new \GuzzleHttp\Client();

        try {
            $res = $client->put(config('app.UPDATE_USER_URL') . $user_id, [\GuzzleHttp\RequestOptions::JSON => $update_array], ['http_errors' => false]);
            $result = (string) $res->getBody(); //turn stream into string
            $result = json_decode($result); //decode string into object
            return $result; //return object
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return (object) array('error' => $e->getResponse()->getBody(true));
        }
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\Employee::with(
            [
                'profile_picture',
                'employment_and_compensation' => function ($q) {
                    $q->select('employee_id', 'date_hired', 'position_id', 'department_id', 'employee_type_id', 'work_schedule_id', 'section_id', 'position_name');
                },
                'employment_and_compensation.department' => function ($q) {
                    $q->select('id', 'department_name', 'code');
                },
                'employment_and_compensation.section' => function ($q) {
                    $q->select('id', 'section_name');
                },
                'employment_and_compensation.position' => function ($q) {
                    $q->select('id', 'position_name', 'item_number');
                },
                'employment_and_compensation.employee_type' => function ($q) {
                    $q->select('id', 'employee_type_name');
                },
                'employment_and_compensation.work_schedule' => function ($q) {
                    $q->select('id', 'work_schedule_name');
                },
            ]
        )
            ->leftJoin('personal_information',  'employees.id', '=', 'personal_information.employee_id');

        if ($request->filled('department_name')) {
            $query->whereHas('employment_and_compensation.department', function ($q) use ($request) {
                $q->whereIn('department_name', $request->input('department_name'));
            });
        }

        if ($request->filled('position_name')) {
            $query->whereHas('employment_and_compensation.position', function ($q) use ($request) {
                $q->whereIn('position_name', $request->input('position_name'));
            });
        }

        if ($request->filled('employee_type_name')) {
            $query->whereHas('employment_and_compensation.employee_type', function ($q) use ($request) {
                $q->whereIn('employee_type_name', $request->input('employee_type_name'));
            });
        }

        if ($request->filled('work_schedule_name')) {
            $query->whereHas('employment_and_compensation.work_schedule', function ($q) use ($request) {
                $q->whereIn('work_schedule_name', $request->input('work_schedule_name'));
            });
        }

        $query = $query->select(
            'employees.id',
            'employees.status',
            'personal_information.first_name',
            'personal_information.middle_name',
            'personal_information.last_name',
            'personal_information.name_extension',
            'personal_information.date_of_birth',
            'personal_information.gender',
            'personal_information.barangay',
            'personal_information.city',
            'personal_information.province',
            'personal_information.p_barangay',
            'personal_information.p_city',
            'personal_information.p_province',
        );

        if ($request->filled('age_range')) {
            $age = $request->input('age_range');
            if (gettype($age) === 'array') {
                $from = Carbon::today()->subYears($age[0]);
                $to = Carbon::today()->subYears($age[1]);
                $query->whereBetween('date_of_birth', [$to, $from]);
            }
        }

        if ($request->filled('birth_month')) {
            $birth_months = $request->input('birth_month');
            $first = true;
            foreach ($birth_months as $birth_month) {
                if ($first) {
                    $query->whereMonth('date_of_birth', $birth_month);
                    $first = false;
                    continue;
                }
                $query->orWhereMonth('date_of_birth', $birth_month);
            }
        }

        if ($request->filled('educational_attainment')) {
            $educational_attainment = $request->input('educational_attainment', 1);
            $query->whereHas('educational_background', function ($q) use ($educational_attainment) {
                $q->where('type', $educational_attainment);
                $q->where(function ($q2) {
                    $q2->whereNotNull('school_name');
                    $q2->orWhereNotNull('course');
                    $q2->orWhereNotNull('year_graduated');
                    $q2->orWhereNotNull('honors');
                    $q2->orWhereNotNull('highest_level');
                    $q2->orWhereNotNull('units_earned');
                    $q2->orWhere('start_year', '!=', 0);
                    $q2->orWhere('start_month', '!=', 0);
                    $q2->orWhere('start_day', '!=', 0);
                    $q2->orWhere('end_year', '!=', 0);
                    $q2->orWhere('end_month', '!=', 0);
                    $q2->orWhere('end_day', '!=', 0);
                });
            });
        }

        if ($request->filled('postgrad_course')) {
            $courses = $request->input('postgrad_course');
            $query->whereHas('educational_background', function ($q) use ($courses) {
                $first = true;
                foreach ($courses as $course) {
                    if ($first) {
                        $q->where('course', 'like', '%' . $course . '%');
                        $first = false;
                        continue;
                    }
                    $q->orWhere('course', 'like', '%' . $course . '%');
                }
            });
        }

        if ($request->filled('undergrad_course')) {
            $courses = $request->input('undergrad_course');
            $query->whereHas('educational_background', function ($q) use ($courses) {
                $first = true;
                foreach ($courses as $course) {
                    if ($first) {
                        $q->where('course', 'like', '%' . $course . '%');
                        $first = false;
                        continue;
                    }
                    $q->orWhere('course', 'like', '%' . $course . '%');
                }
            });
        }

        if ($request->filled('other_information')) {
            $others = $request->input('other_information');
            $query->whereHas('other_information', function ($q) use ($others) {
                foreach ($others as $other) {
                    $q->where('special_skills', 'like', '%' . $other . '%');
                    $q->orWhere('recognition', 'like', '%' . $other . '%');
                    $q->orWhere('organization', 'like', '%' . $other . '%');
                }
            });
        }

        if ($request->filled('trainings')) {
            $trainings = $request->input('trainings');
            $query->whereHas('training_program', function ($q) use ($trainings) {
                $first = true;
                foreach ($trainings as $training) {
                    if ($first) {
                        $q->where('title', 'like', '%' . $training . '%');
                        $first = false;
                        continue;
                    }
                    $q->orWhere('title', 'like', '%' . $training . '%');
                }
            });
        }

        if ($request->filled('experience')) {
            $experiences = $request->input('experience');
            $query->whereHas('voluntary_work', function ($q) use ($experiences) {
                $first = true;
                foreach ($experiences as $experience) {
                    if ($first) {
                        $q->where('position', 'like', '%' . $experience . '%');
                        $first = false;
                        continue;
                    }
                    $q->orWhere('position', 'like', '%' . $experience . '%');
                }
            });
            $query->orWhereHas('work_experience', function ($q) use ($experiences) {
                $first = true;
                foreach ($experiences as $experience) {
                    if ($first) {
                        $q->where('position_title', 'like', '%' . $experience . '%');
                        $first = false;
                        continue;
                    }
                    $q->orWhere('position_title', 'like', '%' . $experience . '%');
                }
            });
            $query->orWhereHas('civilservice_eligibility', function ($q) use ($experiences) {
                $first = true;
                foreach ($experiences as $experience) {
                    if ($first) {
                        $q->where('government_id', 'like', '%' . $experience . '%');
                        $first = false;
                        continue;
                    }
                    $q->orWhere('government_id', 'like', '%' . $experience . '%');
                }
            });
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->whereHas('personal_information', function ($q) use ($search) {
                $q->where('last_name', 'like', '%' . $search . '%');
                $q->orWhere('middle_name', 'like', '%' . $search . '%');
                $q->orWhere('first_name', 'like', '%' . $search . '%');
                $q->orWhere('name_extension', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('gender')) {
            $genders = $request->input('gender');
            $query->whereHas('personal_information', function ($q) use ($genders) {
                $q->whereIn('gender', $genders);
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->whereIn('status', $status);
        }

        $ALLOWED_FILTERS = ['status', 'gender', 'barangay', 'city', 'province', 'p_barangay', 'p_city', 'p_province'];
        $response = $this->paginate($request, $query);

        return response()->json($response);
    }

    public function list_employees_for_dropdown(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\Employee::with('personal_information');

        if ($request->filled('status')) {
            $query->whereIn('status', $request->input('status'));
        }

        $employees = $query->get();

        return response()->json(array(
            'result' => 'success',
            'data' => $employees
        ));
    }

    public function employee_login_information(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        if (!$this->me) {
            return 'Token not authenticated.';
        }

        $query = \DB::table('employees')->where('users_id', $this->me->id)->first();

        if ($query == null) {
            return $this->me;
        }

        $results = array();

        $results['employee_id'] = $query->id;
        $results['users_id'] = $this->me->id;
        $results['user_name'] = $this->me->name;
        $results['direct_report_id'] = \DB::table('employment_and_compensation')
            ->where('emplloyee_id', '=', $query->id)->first()->direct_report_id;
        $results['direct_report'] = \DB::table('employees')
            ->leftJoin('personal_information', 'employees.id', '=', 'personal_information.employee_id')
            ->where('employees.id', '=', $results['direct_report_id'])
            ->select('first_name', 'middle_name', 'last_name')->first();

        $results['mobile_number'] = \DB::table('personal_information')
            ->where('employee_id', '=', $query->id)->first()->mobile_number;

        $results['email'] = $this->me->email;
        $results['department_id'] = $this->me->division;
        $results['departments'] = \DB::table('departments')
            ->where('departments.id', '=', $this->me->division)->first();


        return response()->json($results);
    }

    public function get_card_details(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \DB::table('employees')->where('employees.id', $employee_id)
            ->leftJoin('employment_and_compensation', 'employment_and_compensation.employee_id', 'employees.id')
            ->leftJoin('employee_types', 'employee_types.id', 'employment_and_compensation.employee_type_id')
            ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
            ->leftJoin('positions', 'positions.id', 'employment_and_compensation.position_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('profile_picture', 'profile_picture.employee_id', 'employees.id')
            ->leftJoin('employee_id_number', 'employee_id_number.employee_id', 'employees.id')
            ->select(
                'employees.id',
                'profile_picture.file_location',
                'profile_picture.file_name',
                'profile_picture.file_type',
                'positions.position_name',
                'personal_information.first_name',
                'personal_information.middle_name',
                'personal_information.last_name',
                'personal_information.name_extension',
                'employees.status',
                'employment_and_compensation.date_hired',
                'employment_and_compensation.id_number',
                'employment_and_compensation.employee_type_id as emptype',
                'employee_types.employee_type_name',
                'departments.code',
                'employment_and_compensation.direct_report_id',
                'employment_and_compensation.position_name as pos_name',
                'personal_information.province',
                'personal_information.email_address',
                'personal_information.mobile_number',
                'employee_id_number.id_number'
            )
            ->get();
        $result = [];

        foreach ($query as $item) {
            $array = [];
            $array['id'] = $item->id;
            $array['name'] = "$item->first_name $item->middle_name $item->last_name $item->name_extension";
            $array['employee_name'] = array("first_name" => $item->first_name, "middle_name" => $item->middle_name, "last_name" => $item->last_name, "extension" => $item->name_extension);
            $array['profile_picture'] = array("file_location" => $item->file_location, "file_type" => $item->file_type, "file_name" => $item->file_name);
            $array['status'] = $item->status;
            $array['date_hired'] = $item->date_hired;

            $array['employee_id'] = $item->id_number;
            $array['department_code'] = $item->code;
            $array['position_name'] = $item->position_name ?? $item->pos_name;
            $array['location'] = $item->province;
            $array['mobile_number'] = $item->mobile_number;
            $array['email'] = $item->email_address;
            $array['employeee_type_name'] = $item->employee_type_name;
            $array['direct_report'] = \DB::table('employees')->where('employees.id', $item->direct_report_id)
                ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
                ->select('personal_information.first_name', 'personal_information.middle_name', 'personal_information.last_name')->first();
            $array['direct_report_profile'] = \DB::table('profile_picture')->where('employee_id', $item->direct_report_id)->select('file_location')->first();
            $array['emptype'] = $item->emptype;

            array_push($result, $array);
        }
        return response()->json($result);
    }

    public function get_user_password(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $token = $request->bearerToken();
        $headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json',];
        $urlencode = (string)urlencode(request('password'));

        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request(
                'GET',
                config('app.UPDATE_USER_URL') . 'details/' . $this->me->id . "?password=" . $urlencode,
                ['headers' => $headers]
            );
            $data = $res->getBody();
            $result = json_decode($data);
            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json(json_decode($e->getResponse()->getBody(true)), $e->getResponse()->getStatusCode());
        }
    }

    public function view_employment_history(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        $work_experience = \App\WorkExperience::where('employee_id', $employee->id)
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
            ->get();;
        $employemnt_history = \App\EmploymentHistory::where('employee_id', $employee->id)->get();
        $response['data'] = $work_experience->mergeRecursive($employemnt_history);

        return response()->json($response);
    }

    public function update_employment_history(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'employment_history' => 'required|array',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            foreach ($request->input('employment_history') as $employment_history) {
                $employment_history = (object) $employment_history;
                if ($employment_history->id === -1) {
                    $new_employment_history = \App\EmploymentHistory::create([
                        'employee_id' => $employee->id,
                        'position_id' => $employment_history->position_id,
                        'department_id' => $employment_history->department_id,
                        'start_date' => $employment_history->start_date,
                        'end_date' => $employment_history->end_date,
                    ]);
                } else {
                    $new_employment_history = \App\EmploymentHistory::find($employment_history->id);

                    if (isset($employment_history->deleted) && $employment_history->deleted) {
                        $new_employment_history->delete();
                        continue;
                    }

                    $new_employment_history->employee_id = $employee->id;
                    $new_employment_history->position_id = $employment_history->position_id;
                    $new_employment_history->department_id = $employment_history->department_id;
                    $new_employment_history->start_date = $employment_history->start_date;
                    $new_employment_history->end_date = $employment_history->end_date;
                }

                if (isset($employment_history->status)) {
                    $new_employment_history->status = $employment_history->status;
                }
                if (isset($employment_history->salary)) {
                    $new_employment_history->salary = $employment_history->salary;
                }
                if (isset($employment_history->tranche_version)) {
                    $new_employment_history->tranche_version = $employment_history->tranche_version;
                }
                if (isset($employment_history->branch)) {
                    $new_employment_history->branch = $employment_history->branch;
                }
                if (isset($employment_history->lwop)) {
                    $new_employment_history->lwop = $employment_history->lwop;
                }
                if (isset($employment_history->separation_date)) {
                    $new_employment_history->separation_date = $employment_history->separation_date;
                }
                if (isset($employment_history->separation_cause)) {
                    $new_employment_history->separation_cause = $employment_history->separation_cause;
                }
                if (isset($employment_history->separation_amount_received)) {
                    $new_employment_history->separation_amount_received = $employment_history->separation_amount_received;
                }
                if (isset($employment_history->remarks)) {
                    $new_employment_history->remarks = $employment_history->remarks;
                }
                $new_employment_history->save();
            }

            \DB::commit();
            $history_list = \App\EmploymentHistory::where('employee_id', $employee->id)->get();
            return response()->json(array("data" => $history_list, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function update_job_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $employment_and_compensation = \App\EmploymentAndCompensation::where('employee_id', '=', $employee->id)->first();
        if (!$employment_and_compensation) {
            throw new \Exception('Employee job information not found.');
        }


        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,id',
            'section_id' => 'required|exists:sections,id',
            'employee_type_id' => 'required|exists:employee_types,id',
            'date_hired' => 'required|date_format:Y-m-d',
            'position_id' => 'required_unless:employee_type_id,2,5|exists:positions,id',
            'job_info_effectivity_date' => 'required_unless:employee_type_id,2,5|date_format:Y-m-d',
            'salary_grade_id' => 'required_unless:employee_type_id,2,5|numeric',
            'step_increment' => 'required_unless:employee_type_id,2,5|numeric',
            'position_name' => 'required_if:employee_type_id,2,5|string',
            'salary_rate' => 'required_if:employee_type_id,2,5',
            'start_date' => 'required_if:employee_type_id,2,5||date_format:Y-m-d',
            'period_of_service_start' => 'required_if:employee_type_id,2,5||date_format:Y-m-d',
            'period_of_service_end' => 'required_if:employee_type_id,2,5||date_format:Y-m-d',
            'direct_report_id' => 'sometimes|exists:employees,id'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            if ($request->input('department_id') !== $employment_and_compensation->department_id) {
                $employment_and_compensation->department_id = $request->input('department_id');
            }
            if ($request->input('section_id') !== $employment_and_compensation->section_id) {
                $employment_and_compensation->section_id = $request->input('section_id');
            }
            if ($request->input('employee_type_id') !== $employment_and_compensation->employee_type_id) {
                $this->logInfoChange(
                    $employee->id,
                    'employment_and_compensation',
                    'employee_type_id',
                    $employment_and_compensation->employee_type_id,
                    $request->input('employee_type_id')
                );
                $employment_and_compensation->employee_type_id = $request->input('employee_type_id');
            }
            if ($request->input('date_hired') !== $employment_and_compensation->date_hired) {
                $employment_and_compensation->date_hired = $request->input('date_hired');
            }
            if ($request->input('position_id') !== $employment_and_compensation->position_id) {
                $this->logInfoChange(
                    $employee->id,
                    'employment_and_compensation',
                    'position_id',
                    $employment_and_compensation->position_id,
                    $request->input('position_id')
                );
                $old_position_id = $employment_and_compensation->position_id;
                $employment_and_compensation->position_id = $request->input('position_id');
                // update vacancy
                // vacate old position
                \DB::table('positions')->where('id', '=', $old_position_id)->update(array('vacancy' => \App\Position::VACANCY_UNFILLED));
                // fill new position
                \DB::table('positions')->where('id', '=', $employment_and_compensation->position_id)->update(array('vacancy' => \App\Position::VACANCY_FILLED));
            }
            if ($request->input('job_info_effectivity_date') !== $employment_and_compensation->job_info_effectivity_date) {
                $employment_and_compensation->job_info_effectivity_date = $request->input('job_info_effectivity_date');
            }
            if ($request->input('salary_grade_id') !== $employment_and_compensation->salary_grade_id) {
                $employment_and_compensation->salary_grade_id = $request->input('salary_grade_id');
            }
            if ($request->input('step_increment') !== $employment_and_compensation->step_increment) {
                $employment_and_compensation->step_increment = $request->input('step_increment');
            }
            if ($request->input('position_name') !== $employment_and_compensation->position_name) {
                $employment_and_compensation->position_name = $request->input('position_name');
            }
            if ($request->input('salary_rate') !== $employment_and_compensation->salary_rate) {
                $employment_and_compensation->salary_rate = $request->input('salary_rate');
            }
            if ($request->input('start_date') !== $employment_and_compensation->start_date) {
                $employment_and_compensation->start_date = $request->input('start_date');
            }
            if ($request->input('period_of_service_start') !== $employment_and_compensation->period_of_service_start) {
                $employment_and_compensation->period_of_service_start = $request->input('period_of_service_start');
            }
            if ($request->input('period_of_service_end') !== $employment_and_compensation->period_of_service_end) {
                $employment_and_compensation->period_of_service_end = $request->input('period_of_service_end');
            }
            if ($request->input('direct_report_id') !== $employment_and_compensation->direct_report_id) {
                $employment_and_compensation->direct_report_id = $request->input('direct_report_id');
            }

            $employment_and_compensation->save();


            \DB::commit();
            return response()->json(array("data" => $employment_and_compensation, "result" => "updated"));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_job_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $employment_and_compensation = \App\EmploymentAndCompensation::with([
            'department',
            'section',
            'position.department',
            'employee_type',
        ])
            ->where('employee_id', $employee->id)
            ->first();
        if (!$employment_and_compensation) {
            throw new \Exception('Employee job information not found.');
        }

        return response()->json($employment_and_compensation->only(
            'employee_id',
            'department',
            'department_id',
            'section',
            'section_id',
            'employee_type',
            'employee_type_id',
            'date_hired',
            'position',
            'position_id',
            'job_info_effectivity_date',
            'salary_grade_id',
            'step_increment',
            'position_name',
            'salary_rate',
            'start_date',
            'period_of_service_start',
            'period_of_service_end',
            'direct_report_id'
        ));
    }

    public function update_work_schedule(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $employment_and_compensation = \App\EmploymentAndCompensation::where('employee_id', '=', $employee->id)->first();
        if (!$employment_and_compensation) {
            throw new \Exception('Employee job information not found.');
        }

        $validator = Validator::make($request->all(), [
            'work_schedule_id' => 'required|exists:work_schedules,id',
            'work_sched_effectivity_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            $employment_and_compensation->fill($request->all());
            $employment_and_compensation->save();

            \DB::commit();
            $work_schedule = \App\WorkSchedule::with([
                'breaks',
                'fixed_daily_hours',
                'fixed_daily_times'
            ])
                ->where('id', $employee->employment_and_compensation->work_schedule_id)
                ->first();

            return response()->json(array(
                'work_schedule_id' => $work_schedule->id,
                'work_schedule' => $work_schedule,
                'work_sched_effectivity_date' => $employee->employment_and_compensation->work_sched_effectivity_date,
            ));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_work_schedule(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $work_schedule = \App\WorkSchedule::with([
            'breaks',
            'fixed_daily_hours',
            'fixed_daily_times'
        ])
            ->where('id', $employee->employment_and_compensation->work_schedule_id)
            ->first();

        if (!$work_schedule) {
            throw new \Exception('Employee job information not found.');
        }

        return response()->json(array(
            'work_schedule_id' => $work_schedule->id,
            'work_schedule' => $work_schedule,
            'work_sched_effectivity_date' => $employee->employment_and_compensation->work_sched_effectivity_date,
        ));
    }

    public function update_payroll_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $employment_and_compensation = \App\EmploymentAndCompensation::where('employee_id', '=', $employee->id)->first();
        if (!$employment_and_compensation) {
            throw new \Exception('Employee job information not found.');
        }

        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string',
            'account_number' => 'required|string',
            'gsis_number' => 'required|string',
            'pagibig_number' => 'required|string',
            'philhealth_number' => 'required|string',
            'tin' => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            $employment_and_compensation->fill($request->all());
            $employment_and_compensation->save();

            \DB::commit();
            return response()->json(array(
                "data" => $employment_and_compensation->only(
                    'id',
                    'account_name',
                    'account_number',
                    'gsis_number',
                    'pagibig_number',
                    'philhealth_number',
                    'tin'
                ),
                "result" => "updated"
            ));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function view_payroll_information(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json($employee->employment_and_compensation->only(
            'id',
            'account_name',
            'account_number',
            'gsis_number',
            'pagibig_number',
            'philhealth_number',
            'tin'
        ));
    }

    public function save_offboard(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'employee_id' => 'required|exists:employees,id',
            'reason' => 'required',
            'effectivity' => 'required'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $pending_requests_exist = \App\ApprovalRequest::where([
            ['requestor_id', $request->input('employee_id')],
            ['status', 0]
        ])->exists();
        if ($pending_requests_exist) {
            return response()->json(['error' => 'validation_failed', 'message' => 'Employee has pending approval requests'], 400);
        }

        \DB::beginTransaction();
        try {
            $offboard = \App\Offboard::create([
                'reason' => $request->input('reason'),
                'employee_id' => $request->input('employee_id'),
                'effectivity' => Carbon::createFromFormat('m-d-Y', $request->input('effectivity'))->format('Y-m-d')
            ]);

            if ($request->has('remarks')) {
                $offboard->remarks = $request->input('remarks');
            }
            if ($request->has('attachments')) {
                $offboard->attachments = $request->input('attachments');
            }
            $offboard->save();

            $employee = \App\Employee::find($request->input('employee_id'));
            $employee->status = -1;
            $employee->save();

            $token = $request->bearerToken();
            $this->disable_user($employee->users_id, $token);

            $employment_and_compensation = \App\EmploymentAndCompensation::with([
                'salary',
                'employee_type'
            ])
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'DESC')
            ->first();

            if (!$employment_and_compensation) {
                throw new \Exception('Employee job information not found');
            }

            if (in_array($employment_and_compensation->employee_type_id, [2, 5])) {
                $new_work_experience = \App\WorkExperience::create([
                    'employee_id' => $employee->id,
                    'position_title' => $employment_and_compensation->position_name,
                    'company' => 'NPO',
                    'monthly_salary' => $employment_and_compensation->salary_rate * 12,
                    'pay_grade' => '0',
                    'status_of_appointment' => 'TEMPORARY',
                    'start_inclusive_date' => $employment_and_compensation->period_of_service_start,
                    'end_inclusive_date' => Carbon::createFromFormat('Y-m-d', $offboard->effectivity)->subDays(1)->format('Y-m-d'),
                    'government_service' => true
                ]);
                $employment_and_compensation->position_name = null;
                $employment_and_compensation->salary_rate = null;
                $employment_and_compensation->period_of_service_start = null;
                $employment_and_compensation->period_of_service_end = null;
                $employment_and_compensation->save();
            } else {
                if ($employment_and_compensation->position_id === null) {
                    throw new \Exception('Employee position not found');
                }
                $position = \App\Position::find($employment_and_compensation->position_id);
                if (!$position) {
                    throw new \Exception('Employee position not found');
                }
                $position->vacancy = \App\Position::VACANCY_UNFILLED;
                $position->save();

                $currentLwop = \App\CurrentLwop::where('employee_id', $employment_and_compensation->employee_id)->first();

                $new_employment_history = \App\EmploymentHistory::create([
                    'employee_id' => $employee->id,
                    'position_id' => $employment_and_compensation->position_id,
                    'department_id' => $employment_and_compensation->department_id,
                    'start_date' => $employment_and_compensation->job_info_effectivity_date,
                    'end_date' => Carbon::createFromFormat('Y-m-d', $offboard->effectivity)->subDays(1)->format('Y-m-d'),
                    'status' => $employment_and_compensation->employee_type->employee_type_name,
                    'salary' => $employment_and_compensation->salary->step[$employment_and_compensation->step_increment] * 12, // ANNUAL
                    'branch' => 'Nat\'l',
                    'separation_cause' => $offboard->reason,
                    'separation_date' => $offboard->effectivity,
                    'lwop' => !!$currentLwop ? $currentLwop->lwop : ''
                ]);
                if (!!$currentLwop) {
                    $currentLwop->lwop = '';
                    $currentLwop->save();
                }

                if ($request->has('remarks')) {
                    $new_employment_history->remarks = $request->input('remarks');
                }
                if ($request->has('attachments')) {
                    $new_employment_history->attachments = $request->input('attachments');
                }
                $new_employment_history->save();

                $employment_and_compensation->position_id = null;
                $employment_and_compensation->save();
            }

            foreach ($request->input('attachments', []) as $file) {
                $document = new \App\Document();
                $document->employee_id = $employee->id;
                $document->file_location = $file['url'];
                $document->file_type = $file['type'];
                $document->file_name = $file['name'];
                $document->file_remarks = $request->input('remarks', '');
                $document->file_date = Carbon::parse($offboard->effectivity)->toDateString();
                $document->save();
            }

            \DB::commit();
            return response()->json($offboard);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function save_employee_step_increment(Request $request, \App\Employee $employee) {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            //'remarks' => 'required',
            'effectivity_date' => 'required|date_format:Y-m-d'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }
        // Get salary grade / steps / latest last
        $salaries = \App\Salary::orderBy('id')->get();;
        $lookup = array();
        foreach ($salaries as $salary) {
            $lookup[$salary->grade] = $salary->step;
        }


        try {
            \DB::beginTransaction();
            $grade = $employee->employment_and_compensation->salary_grade_id;
            $step = $employee->employment_and_compensation->step_increment;

            $emp_and_comp = \App\EmploymentAndCompensation::where('employee_id', $employee->id)->first();

            if (sizeof($lookup[$grade]) - 1 == $step) {
                return response()->json(['error' => 'validation_failed', 'messages' => ["Already at maximum step of current grade {$grade}"]], 400);
            }

            $emp_and_comp->step_increment = $step + 1;
            $emp_and_comp->job_info_effectivity_date = request('effectivity_date');
            $emp_and_comp->save();

            // create entry in notice of step increment
            $nosi = new \App\NoticeOfStepIncrement();
            $nosi->employee_id = $employee->id;
            $nosi->generated_date = Carbon::now();
            $nosi->effectivity_date = request('effectivity_date');
            $nosi->old_rate = $lookup[$grade][$step];
            $nosi->new_rate = $lookup[$grade][$step + 1];
            $nosi->old_step = $step;
            $nosi->new_step = $step + 1;
            $nosi->grade = $grade;
            $nosi->position_id = $employee->employment_and_compensation->position_id;
            $nosi->save();

            // create NOSI notification
            $employee->employee_id = $employee->id;
            \App\Notification::create_user_notification(
                $employee->users_id,
                'Your Step has been has Incremented from' .
                ' SG: ' . $grade .
                ' Step: ' . $step . ' to ' .
                ' SG: ' . $grade .
                ' Step: ' . $nosi->new_step .
                ' effective ' . Carbon::parse($nosi->effectivity_date)->format('m/d/Y'),
                \App\Notification::NOTIFICATION_SOURCE_NOSI,
                $employee->id,
                $employee
            );

            \App\Notification::create_hr_notification(
                ['nosi'],
                $employee->name . ' has Incremented from' .
                ' SG: ' . $grade .
                ' Step: ' . $step . ' to ' .
                ' SG: ' . $grade .
                ' Step: ' . $nosi->new_step .
                ' effective ' . Carbon::parse($nosi->effectivity_date)->format('m/d/Y'),
                \App\Notification::NOTIFICATION_SOURCE_NOSI,
                $employee->id,
                $employee
            );

            //create entry in notice of salary adjustment (removed 2022-02-17)
            // $nosa = new \App\NoticeOfSalaryAdjustment();
            // $nosa->employee_id = $employee->id;
            // $nosa->generated_date = Carbon::now();
            // $nosa->effectivity_date = request('effectivity_date');
            // $nosa->old_rate = $lookup[$grade][$step];
            // $nosa->new_rate = $lookup[$grade][$step + 1];
            // $nosa->old_step = $step;
            // $nosa->new_step = $step + 1;
            // $nosa->old_grade = $grade;
            // $nosa->new_grade = $grade;
            // $nosa->old_position_id = $employee->employment_and_compensation->position_id;
            // $nosa->new_position_id = $employee->employment_and_compensation->position_id;
            // if ($request->has('remarks')) {
            //     $nosa->remarks = $request->input('remarks');
            // }
            // $nosa->save();

            // create employee history
            $emp_hist = new \App\EmploymentHistory();
            $emp_hist->employee_id = $employee->id;
            $emp_hist->position_id = $employee->employment_and_compensation->position_id;
            $emp_hist->department_id = $employee->employment_and_compensation->department_id;
            $emp_hist->start_date = $employee->employment_and_compensation->job_info_effectivity_date;
            $emp_hist->end_date = $nosi->effectivity_date;
            $emp_hist->status = $employee->employment_and_compensation->employee_type->employee_type_name;
            $emp_hist->salary = $nosi->old_rate * 12; // ANNUAL
            $emp_hist->branch = "Nat'l";
            if ($request->has('remarks')) {
                $emp_hist->remarks = $request->input('remarks');
            }
            $emp_hist->save();
            \DB::commit();

            return response()->json(['step' => $step, 'result' => $emp_and_comp]);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function save_employee_movement(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'position' => 'required',
            'rate' => 'required',
            'salary_grade' => 'required',
            'step_increment' => 'required',
            //'remarks' => 'required',
            'effectivity_date' => 'required|date_format:Y-m-d'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $pending_requests_exist = \App\ApprovalRequest::where([
            ['requestor_id', $request->input('employee_id')],
            ['status', 0]
        ])->exists();
        if ($pending_requests_exist) {
            return response()->json(['error' => 'validation_failed', 'message' => 'Employee has pending approval requests'], 400);
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $salary_grade = \App\Salary::where([
            'salary_tranche_id' => $active_salary_tranche->id,
            'grade' => $employee->employment_and_compensation->salary_grade_id
        ])->first();
        if (!$salary_grade) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary grade found.'), 400);
        }

        \DB::beginTransaction();
        try {
            $nosa = new \App\NoticeOfSalaryAdjustment();
            $nosa->employee_id = $employee->id;
            $nosa->generated_date = Carbon::now();
            $nosa->effectivity_date = $request->input('effectivity_date');
            $nosa->old_rate = $salary_grade->step[$employee->employment_and_compensation->step_increment];
            $nosa->new_rate = $request->input('rate');
            $nosa->old_step = $employee->employment_and_compensation->step_increment;
            $nosa->new_step = $request->input('step_increment');
            $nosa->old_grade = $employee->employment_and_compensation->salary_grade_id;
            $nosa->new_grade = $request->input('salary_grade');
            $nosa->old_position_id = $employee->employment_and_compensation->position_id;
            $nosa->new_position_id = $request->input('position');
            $nosa->remarks = $request->input('remarks');
            $nosa->save();

            if ($employee->employment_and_compensation->position_id === null) {
                throw new \Exception('Employee position not found');
            }
            $old_position = \App\Position::find($employee->employment_and_compensation->position_id);
            if (!$old_position) {
                throw new \Exception('Employee position not found');
            }
            // set old position to vacant
            $old_position->vacancy = \App\Position::VACANCY_UNFILLED;
            $old_position->save();

            // new position to filled
            $new_position = \App\Position::find($request->input('position'));
            $new_position->vacancy = \App\Position::VACANCY_FILLED;
            $new_position->save();

            $this->logInfoChange(
                $employee->id,
                'employment_and_compensation',
                'position_id',
                $old_position->id,
                $new_position->id
            );

            $employee->employee_id = $employee->id;
            \App\Notification::create_user_notification(
                $employee->users_id,
                'You have an employee movement from ' .
                $old_position->position_name .
                ' SG: ' . $nosa->old_grade .
                ' Step: ' . $nosa->old_step . ' to ' .
                $new_position->position_name .
                ' SG: ' . $nosa->new_step .
                ' Step: ' . $nosa->new_step .
                ' effective ' . Carbon::parse($nosa->effectivity_date)->format('m/d/Y'),
                \App\Notification::NOTIFICATION_SOURCE_NOSA,
                $employee->id,
                $employee
            );

            \App\Notification::create_hr_notification(
                ['nosa'],
                $employee->name . ' has an employee movement from ' .
                $old_position->position_name .
                ' SG: ' . $nosa->old_grade .
                ' Step: ' . $nosa->old_step . ' to ' .
                $new_position->position_name .
                ' SG: ' . $nosa->new_step .
                ' Step: ' . $nosa->new_step .
                ' effective ' . Carbon::parse($nosa->effectivity_date)->format('m/d/Y'),
                \App\Notification::NOTIFICATION_SOURCE_NOSA,
                $employee->id,
                $employee
            );

            $currentLwop = \App\CurrentLwop::where('employee_id', $employee->id)->first();

            $new_employment_history = \App\EmploymentHistory::create([
                'employee_id' => $employee->id,
                'position_id' => $employee->employment_and_compensation->position_id,
                'department_id' => $employee->employment_and_compensation->department_id,
                'start_date' => $employee->employment_and_compensation->job_info_effectivity_date,
                'end_date' => $request->input('effectivity_date'),
                'status' => $employee->employment_and_compensation->employee_type->employee_type_name,
                'salary' => $salary_grade->step[$employee->employment_and_compensation->step_increment] * 12, // ANNUAL
                'branch' => "Nat'l",
                'lwop' => !!$currentLwop ? $currentLwop->lwop : ''
            ]);
            if (!!$currentLwop) {
                $currentLwop->lwop = '';
                $currentLwop->save();
            }

            if ($request->has('remarks')) {
                $new_employment_history->remarks = $request->input('remarks');
            }
            if ($request->has('attachments')) {
                $new_employment_history->attachments = $request->input('attachments');
            }
            $new_employment_history->save();

            foreach ($request->input('attachments', []) as $file) {
                $document = new \App\Document();
                $document->employee_id = $employee->id;
                $document->file_location = $file['url'];
                $document->file_type = $file['type'];
                $document->file_name = $file['name'];
                $document->file_remarks = $request->input('remarks', '');
                $document->file_date = $request->input('effectivity_date');
                $document->save();
            }

            \App\EmploymentAndCompensation::where('employee_id', $employee->id)->update([
                'position_id' => $request->input('position'),
                'salary_grade_id' => $request->input('salary_grade'),
                'step_increment' => $request->input('step_increment'),
                'job_info_effectivity_date' => $request->input('effectivity_date')
            ]);

            \DB::commit();
            return response()->json(['result' => 'success']);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function get_employee_name(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json(array(
            'first_name' => $employee->personal_information->first_name,
            'last_name' => $employee->personal_information->last_name,
        ));
    }

    public function disable_user($user_id, $token)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $client = new \GuzzleHttp\Client();
        try {
            $client->post(
                config('app.UPDATE_USER_URL') . $user_id . '/disable',
                ['headers' => $headers],
                [\GuzzleHttp\RequestOptions::JSON => []],
                ['http_errors' => false]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new \Exception($error['error']);
        }
    }
}
