<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Constants;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $client = new \GuzzleHttp\Client();

        try {
            $res = $client->post(
                config('app.OAUTH_ACCESS_TOKEN_URL'),
                [\GuzzleHttp\RequestOptions::JSON => [
                    'code' => request('code'),
                    'client_id' => config('app.OAUTH_CLIENT_ID'),
                    'client_secret' => config('app.OAUTH_CLIENT_SECRET')
                ]],
                ['http_errors' => false]
            );

            if ($res->getStatusCode() == 200) {
                // update user object
                $response = json_decode($res->getBody(), true);
                $user = \App\User::updateOrCreate(
                    ['id' => $response['user']['id']],
                    [
                        'id' => $response['user']['id'],
                        'name' => $response['user']['name'],
                        'email' => $response['user']['email'],
                        'permissions' => $response['user']['permissions'],
                        'division' => $response['user']['division'],
                        'section' => $response['user']['section']
                    ]
                );
                $user_token = new \App\UserToken();
                $user_token->token = $response['token'];
                $user_token->token_expiry = Carbon::now()->addMinutes($response['token_expiry'])->toDateTimeString();
                $user_token->user_id = $response['user']['id'];
                $user_token->save();

                // TODO: expire old tokens of the user
                $employee_id = \DB::table('employees')
                    ->where('users_id', '=', $response['user']['id'])
                    ->select('id')->first();

                if ($employee_id != null) {
                    $response['employee_id'] = $employee_id->id;
                }
                return response()->json($response);
            } else {
                return response()->json(['response' => json_decode($res->getBody()), 'status' => $res->getStatusCode()]);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json(json_decode($e->getResponse()->getBody(true)), $e->getResponse()->getStatusCode());
        }
    }
}
