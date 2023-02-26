<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SignatoriesController extends Controller
{
  public function list(Request $request)
  {
    // $unauthorized = $this->is_not_authorized(['view_signatories']);
    // if ($unauthorized) {
    //     return $unauthorized;
    // }

    $query = \App\Signatories::select('*');

    $ALLOWED_FILTERS = [];
    $SEARCH_FIELDS = ['report_name'];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function update(Request $request)
  {
    // $unauthorized = $this->is_not_authorized(['update_signatories']);
    // if ($unauthorized) {
    //     return $unauthorized;
    // }

    $validator_arr = [
      'signatories' => 'required',
      'id' => 'required',
    ];

    $validator = \Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    \DB::beginTransaction();
    try {
      $signatory = \App\Signatories::find(request('id'));
      // $signatory->report_name = request('report_name');
      $signatory->signatories = request('signatories');
      // $signatory->signatories_count = request('signatories_count');
      $signatory->save();
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
    \DB::commit();

    return response()->json($signatory);
  }
}
