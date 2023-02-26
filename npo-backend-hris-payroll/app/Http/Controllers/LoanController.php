<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class LoanController extends Controller
{
  public function create_or_update(Request $request, \App\Loan $loan, $is_new = false)
  {
    $validator_arr = [
      'loan_name' => 'required',
      'category' => 'sometimes|in:philhealth,gsis,pagibig,npompc,nhmfc'
    ];

    $validator = Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    $loan->loan_name = $request->input('loan_name');
    $loan->category = $request->input('category');

    \DB::beginTransaction();
    try {
      if ($is_new) {
        $loan->created_by = $this->me->id;
        $loan->status = true;
        $loan->save();
      } else {
        $loan->updated_by = $this->me->id;
        $loan->save();
      }
      \DB::commit();
      return response()->json($loan);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_loan']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\Loan(), true);
  }

  public function update(Request $request, \App\Loan $loan)
  {
    $unauthorized = $this->is_not_authorized(['edit_loan']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $loan);
  }

  public function read(Request $request, \App\Loan $loan)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    return response()->json($loan);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    $query = \App\Loan::select('*');

    // filtering
    $ALLOWED_FILTERS = ['status'];
    $SEARCH_FIELDS = [];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
    return response()->json($result);
  }

  public function set_status(Request $request, \App\Loan $loan)
  {
    $unauthorized = $this->is_not_authorized(['toggle_loan']);
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

    $ongoing_count = \App\LoanRequest::where([
      ['status', '1'],
      ['loan_type_id', $loan->id]
    ])
      ->whereRaw('
            loan_requests.loan_amount > (
                SELECT IFNULL(SUM(loan_payments.amount), 0)
                FROM loan_payments
                WHERE loan_payments.loan_request_id = loan_requests.id
            )
        ')->count();

    if ($ongoing_count > 0 && !$request->input('status')) {
      return response()->json(['error' => 'Cannot deactivate loan', 'messages' => "Loan has " . $ongoing_count . " ongoing request(s)"], 400);
    }

    \DB::beginTransaction();
    try {
      $loan->updated_by = $this->me->id;
      $loan->status = $request->input('status');
      $loan->save();

      if (!$loan->status) {
        \App\LoanRequest::where([
          ['status', 0],
          ['loan_type_id', $loan->id]
        ])
          ->update(['status' => -1]);
      }
      \DB::commit();
      return response()->json(array("result" => "Updated Success", "data" => $loan));
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }
}
