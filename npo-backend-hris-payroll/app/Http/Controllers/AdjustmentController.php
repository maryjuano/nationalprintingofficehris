<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class AdjustmentController extends Controller
{

    public function create_or_update(Request $request, \App\Adjustment $adjustment, $is_new = false)
    {
        $validator_arr = [
            'type' => 'required',
            'tax' => 'required'
        ];

        if ($is_new) {
            $validator_arr['adjustment_name'] = 'required|unique:adjustments';
            $validator_arr['short_name'] = 'required|string|min:1|max:15|unique:adjustments';
        } else {
            $validator_arr['adjustment_name'] = "required|unique:adjustments,adjustment_name,$adjustment->id";
            $validator_arr['short_name'] = "required|string|min:1|max:15|unique:adjustments,short_name,$adjustment->id";
        }

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        // validations
        if ((request('tax') == 0 || request('type') != 0) && request('ceiling') > 0) {
            return response()->json(['error' => 'validation_failed', 'messages' => ['Ceiling must be 0 for non-Earnings or Taxable Earnings ']], 400);
        }
        if (in_array(request('adjustment_name'), \App\Adjustment::NO_DEFAULT_ADJUSTMENTS) && request('default_amount') > 0) {
            return response()->json(['error' => 'validation_failed', 'messages' => [request('adjustment_name') . ' cannot have a default value']], 400);
        }

        //required fields
        if (!$adjustment->read_only) {
            $adjustment->adjustment_name = request('adjustment_name');
            $adjustment->type = request('type');
            $adjustment->tax = request('tax');
        }
        
        $adjustment->short_name = request('short_name');

        //not required fields
        $adjustment->status = request('status', 1);
        $adjustment->category = request('category', null);
        $adjustment->ceiling = request('ceiling', null);
        $adjustment->default_amount = request('default_amount', null);

        // convert to read-only
        if (in_array($adjustment->adjustment_name, \App\Adjustment::READ_ONLY_ADJUSTMENTS)) {
            $adjustment->read_only = true;
        }

        \DB::beginTransaction();
        try {
            if ($is_new) {
                $adjustment->created_by = $this->me->id;
                $adjustment->updated_by = $this->me->id;
                $adjustment->save();
            } else {
                $adjustment->updated_by = $this->me->id;
                $adjustment->save();
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        return response()->json($adjustment);
    }

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_adjustment']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, new \App\Adjustment(), true);
    }

    public function update(Request $request, \App\Adjustment $adjustment)
    {
        $unauthorized = $this->is_not_authorized(['edit_adjustment']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, $adjustment);
    }

    public function read(Request $request, \App\Adjustment $adjustment)
    {
        $unauthorized = $this->is_not_authorized(['view_adjustments']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return response()->json($adjustment);
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_adjustments']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $query = \App\Adjustment::select('*');

        // filtering
        $ALLOWED_FILTERS = ['status', 'type', 'tax', 'category'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        // $data = $response['data'];
        // $adjustments = array();
        // foreach ($data as $adjustment) {
        //     $adjustments_item = array();
        //     $adjustments_item['adjustment_name'] = $adjustment->adjustment_name;
        //     $adjustments_item['type'] = $adjustment->type;
        //     $adjustments_item['tax'] = $adjustment->tax;
        //     $adjustments_item['id'] = $adjustment->id;
        //     $adjustments_item['status'] = $adjustment->status == 1 ? true:false;
        //     $adjustments_item['created_at'] = Carbon::parse($adjustment->created_at);
        //     $adjustments_item['updated_at'] = Carbon::parse($adjustment->updated_at);
        //     array_push($adjustments, $adjustments_item);
        // }

        // $response['data'] = $adjustments;
        return response()->json($response);
    }

    public function set_status(Request $request, \App\Adjustment $adjustment)
    {
        $unauthorized = $this->is_not_authorized(['edit_adjustment']);
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

        if ($adjustment->read_only) {
            return response()->json(['error' => 'validation_failed', 'messages' => ['This adjustment cannot be disabled']], 400);
        }

        //required fields
        $adjustment->status = request('status');

        \DB::beginTransaction();
        try {
            $adjustment->save();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        return response()->json($adjustment);
    }
}
