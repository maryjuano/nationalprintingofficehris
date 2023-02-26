<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class _TemplateController extends Controller
{
  public function create_or_update(Request $request, \App\_TModel $_tmodel, $is_new = false)
  {

    $validator_arr = [
      // TODO
    ];

    $validator = Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }
    // actual creation
    $_tmodel->fill(
      // TODO
      $request->only([])
    );

    // TODO: fill up other non-required fields
    $_tmodel->is_special = request('is_special', false);

    // TODO: save
    \DB::beginTransaction();
    try {
      if ($is_new) {
      } else {
      }
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
    \DB::commit();

    $_tmodel = \App\_TModel::where('id', '=', $_tmodel->id)->first();
    $_tmodel = $this->enrich($_tmodel);
    return response()->json(array('_tmodel' => $_tmodel, 'result' => $result));
    // response
  }

  public function enrich($_tmodel)
  {
    // TODO
    return $_tmodel;
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\_TModel(), true);
  }

  public function read(Request $request, \App\_TModel $_tmodel)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    return response()->json($_tmodel);
  }

  public function update(Request $request, \App\_TModel $_tmodel)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $_tmodel);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    $query = \DB::table('_TModels');
    $query = $query->select('TODO');

    // filtering
    $ALLOWED_FILTERS = ['TODO'];
    $SEARCH_FIELDS = [];
    $JSON_FIELDS = ['TODO'];
    $BOOL_FIELDS = [];
    $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
    return response()->json($result);
  }

  public function delete(Request $request, \App\_TModel $_tmodel)
  {
    $this->me = JWTAuth::parseToken()->authenticate();
    if (!($this->me->permissions['temporary'] ?? $this->DISABLE_AUTH)) {
      return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
    }
    $_tmodel->delete();
    return response()->json(array('_tmodel' => $_tmodel, 'result' => 'deleted'));
  }
}
