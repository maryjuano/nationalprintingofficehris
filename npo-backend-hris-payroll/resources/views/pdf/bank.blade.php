<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>
/*
    table {
        border-left: 0.01em solid #ccc;
        border-right:  0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom:  0.01em solid #ccc;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        padding: 8
    } */

     @font-face {
        font-family: 'Helvetica';
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
      }

      body {
        font-family: Helvetica, sans-serif;
      }



    </style>

        <body>
            @foreach($vals['data'] as $item_key => $value)
            <div>
            @php
                $left_side = $value->id_number.$value->last_name.", ".$value->first_name." ".$value->middle_name.".";
                $last_digit = 18700032;
                $zero = $value->net_pay;
                $middle_digit = number_format($zero, 2, '', '');
                $first_digit = str_pad($middle_digit, 15, '0', STR_PAD_LEFT);

                $right_side = $first_digit.$last_digit;
            @endphp

            <table width="100%">
                <tr>
                    <td style="width: 60%">
                        {{ $left_side }}
                    </td>

                    <td style="width: 40%">
                      {{ $right_side }}
                    </td>
                </tr>
            </table>

            </div>
            @endforeach
        </body>
    </html>

