<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>
        @page { size: 22cm 33cm landscape; }
        @font-face {
            font-family: 'Helvetica';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
        }
        .table {
            border: 0.01em solid #000;
            width: 100%;
            box-shadow: 1px 1px 10px solid #ccc;
        }
        body {
            font-family: Helvetica, sans-serif;
        }
        .header {
            font-size: 0.7em;
            border: 0.01em solid #000;
            text-align: center;
            padding: 5px 0px;
            margin: 0px;
            font-weight :bold;
            background-color : #d3d3d3;
        }
        .values {
            font-size: 0.8em;
            border: 0.01em solid #000;
            text-align: left;
            padding: 5px 0px 5px 5px;
            word-wrap:break-word
        }
        .bg-cell {
            background-color : #d3d3d3;
            font-weight :bold;
        }
        .center-text {
            text-align : center;
        }
        .bold-text  {
            font-weight : bold;
        }
        .to-uppercase {
            text-transform : uppercase;
        }
        .title {
            font-size: 0.8em;
            text-align: center;
            font-weight :bold;
        }

    </style>
        <body>
            @php
                $summary =[];
                $totalPos = 0;
                $totalFilled = 0;
                $totalUnfilled = 0;

                function isVacant($item){
                    return !isset($item['account_name']) ?  'color : red;' : '';
                }
            @endphp
            <div>
                <div class="title">
                    <div>
                        NATIONAL PRINTING OFFICE
                    </div>
                    <div>
                        PLANTILLA OF PERSONNEL
                    </div>
                    <div>
                        FY {{ date('Y') }} (AS OF {{strtoupper(date('F d, Y'))}})
                    </div>
                </div>
                <table class="table" style="border-collapse: collapse; margin-top : 10px;">
                    <tr>
                        <th colspan="3" class="header" > ITEM NUMBER </th>
                        <th colspan="6" class="header" > POSITION </th>
                        <th colspan="1" class="header" style="font-size: 0.6em; width: 70px"> SALARY<br>GRADE </th>
                        <th colspan="1" class="header" style="font-size: 0.6em; width: 70px"> STEP<br>INCREMENT </th>
                        <th colspan="6" class="header" > NAME OF INCUMBENT </th>
                        <th colspan="2" class="header" > MONTHLY SALARY </th>
                        <th colspan="2" class="header" > ANNUAL SALARY </th>
                    </tr>
                    @foreach ( $departments as $department)
                        @php
                            $total = 0;
                            $filled = 0;
                            $unfilled = 0;
                        @endphp
                        <tr>
                            <td class="values bg-cell" colspan="21" > {{strtoupper($department->department_name)}} </td>
                        </tr>

                        @foreach (isset($department->department_name) ? $department->positions : [] as $item)
                            @php
                                $steps = json_decode($item->step, true);
                                $salary = $steps[isset($item->step_increment) ? $item->step_increment : 0 ];
                                $monthly_salary = number_format($salary, 2, '.', ',');
                                $annual_salary = number_format($salary * 12, 2, '.', ',');
                                $total+=1;
                                if(isset($item->step_increment)){
                                    $filled+=1;
                                }else{
                                    $unfilled+=1;
                                }
                            @endphp
                            <tr>
                                <td colspan="3" class="values center-text"> {{ $item->item_number }} </td>
                                <td colspan="6" class="values center-text to-uppercase"> {{ $item->position_name }} </td>
                                <td colspan="1" class="values center-text"> {{ $item->salary_grade_id }} </td>
                                <td colspan="1" class="values center-text"> {{ isset($item->step_increment) ? $item->step_increment : 0 }} </td>
                                <td colspan="6" class="values center-text to-uppercase" style="{{ isVacant($item) }}" >
                                    {{ isset($item->full_name) ? $item->full_name : 'VACANT' }}
                                </td>
                                <td colspan="2" class="values center-text"> {{ $monthly_salary }} </td>
                                <td colspan="2" class="values center-text"> {{ $annual_salary }} </td>
                            </tr>
                        @endforeach
                        {{-- @foreach (isset($department->department_name) ? $department->unfilled_positions : [] as $item)
                            @php
                                $steps = json_decode($item->step, true);
                                $salary = $steps[isset($item->step_increment) ? $item->step_increment : 0 ];
                                $monthly_salary = number_format($salary, 2, '.', ',');
                                $annual_salary = number_format($salary * 12, 2, '.', ',');
                                $total+=1;
                                if(isset($item->step_increment)){
                                    $filled+=1;
                                }else{
                                    $unfilled+=1;
                                }
                            @endphp
                            <tr>
                                <td colspan="1" class="values center-text"> {{ $item->item_number }} </td>
                                <td colspan="3" class="values center-text to-uppercase"> {{ $item->position_name }} </td>
                                <td colspan="1" class="values center-text"> {{ $item->salary_grade_id }} </td>
                                <td colspan="1" class="values center-text"> {{ isset($item->step_increment) ? $item->step_increment : 0 }} </td>
                                <td colspan="2" class="values center-text to-uppercase" style="{{ isVacant($item) }}" >
                                    {{ isset($item->full_name) ? $item->full_name : 'VACANT' }}
                                </td>
                                <td colspan="2" class="values center-text"> {{ $monthly_salary }} </td>
                                <td colspan="2" class="values center-text"> {{ $annual_salary }} </td>
                            </tr>
                        @endforeach --}}
                        @php
                            $summary[$department->department_name]=[
                                'total'=>$total,
                                'filled'=>$filled,
                                'unfilled'=>$unfilled
                        ];
                            $totalPos += $total;
                            $totalFilled += $filled;
                            $totalUnfilled += $unfilled;
                        @endphp
                    @endforeach
                </table>
                <div style="page-break-after:always;"></div>
                <div style="margin-top : 50px" class="bold-text" >PERSONNEL COMPLEMENT FY {{ date('Y') }} (as of {{date('F d, Y')}})</div>
                <table style="margin-top : 20px; width: 70%; border-bottom :2px solid ; padding-bottom: 20px;">
                    <tr>
                        <td style="width : 40%" class="bold-text">Total Number (Per Division)</td>
                        <td style="width : 10%"  class="bold-text center-text">ITEMIZED</td>
                        <td style="width : 10%"  class="bold-text center-text">UNFILLED</td>
                        <td style="width : 10%" class="bold-text center-text">FILLED</td>
                    </tr>
                    @foreach ($summary as $key=>$item)
                        <tr>
                            <td style="width : 40%"  class="" >{{strtoupper($key)}}</td>
                            <td style="width : 10%;" class="center-text">{{$item['total']}}</td>
                            <td style="width : 10%;" class="center-text">{{$item['unfilled']}}</td>
                            <td style="width : 10%;" class="center-text">{{$item['filled']}}</td>
                        </tr>
                    @endforeach
                </table>
                <table style="margin-top : 10px; width: 70%;">
                    <tr>
                        <td style="width : 40%; text-align : right; padding-right : 50px;" class="bold-text" >TOTAL</td>
                        <td style="width : 10%"  class="bold-text center-text">{{$totalPos}}</td>
                        <td style="width : 10%"  class="bold-text center-text">{{$totalUnfilled}}</td>
                        <td style="width : 10%" class="bold-text center-text">{{$totalFilled}}</td>
                    </tr>
                </table>

            </div>
        </body>
    </html>

