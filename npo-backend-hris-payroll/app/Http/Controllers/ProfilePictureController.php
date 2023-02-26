<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use \App\ProfilePicture;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ProfilePictureController extends Controller
{
    public function save_update_profile(Request $request, $new = false, \App\ProfilePicture $profile_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        // $table = new ProfilePicture();

        $validator_arr = [
            'employee_id' => 'required',
            'file_location' => 'required',
            'file_type' => 'required',
            'file_name' => 'required'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $profile_id->fill(
            $request->only(['employee_id', 'file_location', 'file_type', 'file_name'])
        );

        \DB::beginTransaction();
        try {
            if ($new === true) {
                $profile_id->save();
                $result = "save";
            } else {
                $result = "update";
                $profile_id->save();
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        return response()->json(array("data" => $profile_id, "result" => $result));
    }

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->save_update_profile($request, $new = true, new \App\ProfilePicture);
    }

    public function update(Request $request, \App\ProfilePicture $profile_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->save_update_profile($request, $new = false, $profile_id);
    }

    public function read(Request $request, $profile_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\ProfilePicture::where('employee_id', $profile_id)->get();
        return response()->json($query);
    }
}
