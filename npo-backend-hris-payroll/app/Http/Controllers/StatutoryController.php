<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use \App\StatutoryGSIS;
use Carbon\Carbon;

class StatutoryController extends Controller
{
    public function view()
    {
        $unauthorized = $this->is_not_authorized(['view_gsis']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \DB::table('gsis')->get();

        return response()->json(array("data" => $query));
    }


    public function update(Request $request, \App\StatutoryGSIS $statutory)
    {
        $unauthorized = $this->is_not_authorized(['update_gsis']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $pending_payrun = \App\Payrun::whereIn('status', [0, 1])->count();

        if ($pending_payrun > 0) {
            return response()->json(['error' => 'Cannot modify GSIS.', 'messages' => "There is/are " . $pending_payrun . " pending payrun(s)"], 400);
        }

        $statutory->ecc = request('ecc');
        $statutory->personal_share = request('personal_share');
        $statutory->government_share = request('government_share');

        \DB::beginTransaction();
        try {
            $statutory->save();
            \DB::commit();
            return response()->json($statutory);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function activate(Request $request, \App\StatutoryGSIS $statutory)
    {
        $unauthorized = $this->is_not_authorized(['toggle_gsis']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $statutory->status = true;
        $statutory->save();

        return response()->json(array('data' => $statutory, 'result' => 'activated'));
    }

    public function deactivate(Request $request, \App\StatutoryGSIS $statutory)
    {
        $unauthorized = $this->is_not_authorized(['toggle_gsis']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $pending_payrun = \App\Payrun::whereIn('status', [0, 1])->count();

        if ($pending_payrun > 0) {
            return response()->json(['error' => 'Cannot change status.', 'messages' => "There is/are " . $pending_payrun . " pending payrun(s)"], 400);
        }

        $statutory->status = false;
        $statutory->save();

        return response()->json(array('data' => $statutory, 'result' => 'deactivated'));
    }

    public function add_pagibig(Request $request, \App\StatutoryPagibig $statutory, $is_new = true)
    {
        $unauthorized = $this->is_not_authorized(['add_pagibig']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'minimum_range' => 'required',
            'maximum_range' => 'required',
            'personal_share' => 'required',
            'government_share' => 'required',
        ];


        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        if (!$is_new) {
            $statutory->minimum_range = -1;
            $statutory->maximum_range = -1;
            $statutory->save();
        }

        $pagibig_table = \DB::table('pagibig')->get();
        foreach ($pagibig_table as $data) {
            if ($request->minimum_range >= $data->minimum_range && $request->minimum_range <= $data->maximum_range) {
                return response()->json(['error' => 'validation_failed', 'message' => "Minimum range overlaps with existing range of data"], 400);
            }
            if ($request->maximum_range >= $data->minimum_range && $request->maximum_range <= $data->maximum_range) {
                return response()->json(['error' => 'validation_failed', 'message' => "Maximum range overlaps with existing range of data"], 400);
            }
        }

        $statutory->minimum_range = $request['minimum_range'];
        $statutory->maximum_range = $request['maximum_range'];
        $statutory->personal_share = $request['personal_share'];
        $statutory->government_share = $request['government_share'];
        // $statutory->fill(
        //     // TODO
        //     $request->only(['minimum_range', 'maximum_range', 'personal_share', 'government_share'])
        // );

        // TODO: save
        \DB::beginTransaction();
        try {
            if ($is_new) {
                $statutory->save();
                $result = 'created';
            } else {
                $statutory->save();
                $result = 'updated';
            }
            \DB::commit();
            $statutory = \App\StatutoryPagibig::where('id', '=', $statutory->id)->first();
            return response()->json(array('data' => $statutory, 'result' => $result));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function update_pagibig(Request $request, \App\StatutoryPagibig $statutory)
    {
        $unauthorized = $this->is_not_authorized(['add_pagibig']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->add_pagibig($request, $statutory, false);
    }

    public function read_pagibig(Request $request, \App\StatutoryPagibig $statutory)
    {
        $unauthorized = $this->is_not_authorized(['view_pagibig']);
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json($statutory);
    }

    public function list_pagibig(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_pagibig']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\StatutoryPagibig::orderBy('minimum_range', 'ASC');

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($result);
    }

    public function add_philhealth(Request $request, \App\StatutoryPhilhealth $statutory, $is_new = true)
    {
        $unauthorized = $this->is_not_authorized(['add_philhealth']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'minimum_range' => 'required',
            'maximum_range' => 'required',
            'personal_share' => 'required',
            'government_share' => 'required',
            'monthly_premium' => 'required',
            'is_max' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        if (!$is_new) {
            $statutory->minimum_range = -1;
            $statutory->maximum_range = -1;
            $statutory->save();
        }

        $philhealth_table = \DB::table('philhealth')->get();
        foreach ($philhealth_table as $data) {
            if ($request->minimum_range >= $data->minimum_range && $request->minimum_range <= $data->maximum_range) {
                return response()->json(['error' => 'validation_failed', 'message' => "Minimum range overlaps with existing range of data"], 400);
            }
            if ($request->maximum_range >= $data->minimum_range && $request->maximum_range <= $data->maximum_range) {
                return response()->json(['error' => 'validation_failed', 'message' => "Maximum range overlaps with existing range of data"], 400);
            }
        }

        $statutory->minimum_range = $request['minimum_range'];
        $statutory->maximum_range = $request['maximum_range'];
        $statutory->personal_share = $request['personal_share'];
        $statutory->government_share = $request['government_share'];
        $statutory->monthly_premium = $request['monthly_premium'];
        $statutory->is_max = $request['is_max'];
        $statutory->percentage = $request['percentage'];

        // $statutory->fill(
        //     // TODO
        //     $request->only(['minimum_range', 'maximum_range', 'personal_share', 'government_share', 'monthly_premium'])
        // );

        // TODO: save
        \DB::beginTransaction();
        try {
            if ($is_new) {
                $statutory->save();
                $result = 'created';
            } else {
                $statutory->save();
                $result = 'updated';
            }
            \DB::commit();
    
            $statutory = \App\StatutoryPhilhealth::where('id', '=', $statutory->id)->first();
            return response()->json(array('data' => $statutory, 'result' => $result));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function update_philhealth(Request $request, \App\StatutoryPhilhealth $statutory)
    {
        $unauthorized = $this->is_not_authorized(['add_philhealth']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->add_philhealth($request, $statutory, false);
    }

    public function read_philhealth(Request $request, \App\StatutoryPhilhealth $statutory)
    {
        $unauthorized = $this->is_not_authorized(['view_philhealth']);
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json($statutory);
    }

    public function list_philhealth(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_philhealth']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\Philhealth::orderBy('minimum_range', 'ASC');

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($result);
    }
}
