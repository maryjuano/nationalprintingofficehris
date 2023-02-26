<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrentLwopController extends Controller
{
    public function read(Request $request, $employeeId) {
        $unauthorized = $this->is_not_authorized([]);
        if ($unauthorized) {
            return $unauthorized;
        }

        $currentLwop = \App\CurrentLwop::where('employee_id', $employeeId)->first();
        return response()->json(['data' => $currentLwop]);
    }

    public function update(Request $request, $employeeId) {
        $unauthorized = $this->is_not_authorized([]);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'lwop' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $currentLwop = \App\CurrentLwop::firstOrNew(['employee_id' => $employeeId]);
        $currentLwop->lwop = $request->input('lwop');
        $currentLwop->save();

        return response()->json(['result' => 'success', 'data' => $currentLwop]);
    }
}
