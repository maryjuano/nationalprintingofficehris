<?php

// function sample($data)
// {

//     $array = [];

//     if (!in_array($data, $array)) {
//         array_push($array, $data);
//     } else {
//         foreach ($array as $key => $value) {
//             if ($value->title === $data->title) {
//                 $datum = $array[$key];
//                 $amount = $datum->amount + $data->amount;
//                 $final = array("title" => $value->title, "amount" => $amount);
//                 $check_spilice = array_splice($array, 1, $key);

//                 if ($check_spilice) {
//                     array_push($array, $final);
//                 }
//             }
//         }
//     }

//     Session::put('key', json_encode($array));
// }

// function hello()
// {
//     $data = Session::get("key");
//     return $data;
// }
