<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>

     table {
        border-left: 0.01em solid #ccc;
        border-right:  0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom:  0.01em solid #ccc;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
    }

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

      .label1 {
        letter-spacing: 1.5px;
        color: #383838;
        text-transform: uppercase;
        opacity: 1;
        font-size: 11px
      }

      .label2 {
        letter-spacing: 3.6px;
        color: #676767;
        text-transform: uppercase;
        opacity: 1;
        font-size: 10px
      }

      .label3 {
        letter-spacing: 2.6px;
        opacity: 1;
        font-size: 9px
      }

      .borders-td {
        border-left: 0.01em solid #ccc;
        border-right:  0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom:  0.01em solid #ccc;
      }

      .entries {
        letter-spacing: 1px;
        color: #415A85;
        font-size: 9px;
        opacity: 1;
      }

      .entries-value {
        letter-spacing: 1px;
        color: #10336E;
        font-size: 9px;
        opacity: 1;
        margin-left: 20px;
      }

      .entries-last {
        letter-spacing: 1px;
        color: #676767;
        opacity: 1;
        font-size: 7px;
      }

      .main {
        letter-spacing: 1px;
        color: #415A85;
        font-size: 8px;
        opacity: 1;
      }

      .borders-td {
        border: 1px solid #ddd;
      }

    </style>
    <body>
    @foreach(array_chunk($vals['data'], 10) as $employee_chunk)

    <div style="page-break-after:always;">
            <table>
                <tr>
                    <td style="width: 100%">
                    <center class="header-name">
                        <strong class="label1">National Printing Office</strong><br/>
                        <label class="label2">Regular Payroll Registry report</label><br/>
                        <label class="label3">Period of JANUARY 1-15, 2019</label>
                     </center>
                    </td>
                </tr>
            </table>

            <table style="padding: 2">
                <tr>
                    <td style="width: 20%" class="entries">
                        Entries on: <span class="entries-value">---</span> <br/>
                        Payroll by: <span class="entries-value">Juan Miguel Dela Cruz</span> <br/>
                        Cards by: <span class="entries-value">&nbsp;Juana Dela Cruz</span> <br/>
                    </td>
                    <td style="width: 35%" class="entries">
                        Payroll Name: <span class="entries-value">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;All Regular Employees</span> <br/>
                        Deduction Period: <span class="entries-value">Dec 16-30, 2018</span> <br/>
                        Date Generated: <span class="entries-value">&nbsp;&nbsp;January 15, 2019</span> <br/>
                    </td>
                    <td style="width: 40%" >
                    <br/>
                     <span class="entries-last">We acknowledge receipt of cash down opposite our name as full compensation for services rendered for the period covered</span>
                    </td>
                </tr>
            </table>

            <div style=" width: 50px; border-top: 2px dashed #E7E7E7; width: 100%;  margin-top: 5px "></div>

            <div>
            <table style=" margin-top: 5px; width; 100%; border-collapse: collapse;">
                  <tr>
                      <td style="width: 20px; padding: 10 0" class="main borders-td"><center>NO.</center></td>
                      <td class="main borders-td"  style="width: 100px"><center>Employee Name</center></td>
                      <td class="main borders-td" style="width: 144px">
                          <center>Position / Salary Grade</center>
                      </td>
                      <td class="main borders-td" style="width: 70px"> <center>Monthly Salary</center></td>
                      <td class="main borders-td" style="width: 172px">
                          <center> E A R N I N G S</center>
                      </td>
                      <td class="main borders-td" style="width: 60px"> <center> Gross Amount </center></td>
                      <td style="width: 50em" class="main borders-td"> <center> D E D U C T I O N S </center></td>
                      <td class="main borders-td"><center> Total Deduction</center></td>
                      <td class="main borders-td">  <center>  NET AMOUNT</center> </td>
                      <td class="main borders-td"  style="width: 100px">  <center> Signature</center> </td>
                  </tr>
              </table>
            </div>



        @foreach($employee_chunk as $item_key => $value)
          @php
            $salary = \DB::table('employment_and_compensation')->where('employee_id', $value->employee_id)->first()->salary_grade_id;
            $total = 0;

            foreach($value->Deductions as $item) {
              $total = $total + $item->amount;
            }

          @endphp
            <table style=" width; 100%; border-collapse: collapse;" >
                <tr>
                    <td class="main borders-td " style="width: 18px"><center>{{ $item_key+ 1 }}</center></td>
                    <td class="main borders-td " style="width: 100px"><center> {{ $value->name->last_name }}, {{ $value->name->first_name }} {{ $value->name->middle_name }}.</center></td>
                    <td class="main borders-td " style="width: 144px">
                        <center>{{ $value->position_name->position_name}}/SG {{$salary}}</center>
                    </td>
                    <td class="main borders-td " style="width: 70px"> <center>{{ number_format($value->pay_structure->monthlyRate, 2, '.', ',') }}</center></td>

                    @if(count($value->Earnings) <= 5 && count($value->Earnings) >= 2)
                      <td class="main borders-td " style="width: 172px">  @foreach($value->Earnings as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; ">{{ $earn->title}}</span> <span  >  {{ number_format($earn->amount, 2,'.',',')}}</span></center>@endforeach
                      </td>
                    @elseif(count($value->Earnings) <= 1)
                      <td class="main borders-td " style="width: 172px">  @foreach($value->Earnings as $earning => $earn)<center style="padding: 3px 0px">   <span style="color: #B6BBC2; ">{{ $earn->title}}</span> <span  >  {{ number_format($earn->amount, 2,'.',',')}}</span></center>@endforeach
                      </td>
                    @else
                      @foreach(array_chunk($value->Earnings, 10) as $earn_key)
                      <td class="main borders-td " style="width: 20px">  @foreach($earn_key as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; float:left ">{{ $earn->title }}</span> <div style="text-align: right">  {{number_format($earn->amount, 2,'.',',')}}</div></center>@endforeach
                      </td>
                      @endforeach
                    @endif

                    <td class="main borders-td " style="width: 60px"> <center> {{ number_format($value->gross_pay, 2, '.', ',') }} </center></td>

                    @if(count($value->Deductions) >= 5 && count($value->Deductions) <= 10)
                      @foreach(array_chunk($value->Deductions, 5) as $earn_key)
                      <td class="main borders-td " style="width: 198.5px">  @foreach($earn_key as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span> <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div></center>@endforeach
                      </td>
                      @endforeach
                    @elseif(count($value->Deductions) >= 11 && count($value->Deductions) <= 15)
                      @foreach(array_chunk($value->Deductions, 5) as $earn_key)
                      <td class="main borders-td " style="width: 131.5px">  @foreach($earn_key as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span> <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div></center>@endforeach
                      </td>
                      @endforeach
                    @elseif(count($value->Deductions) >= 16 && count($value->Deductions) <=20)
                      @foreach(array_chunk($value->Deductions, 5) as $earn_key)
                      <td class="main borders-td " style="width: 97.8px">  @foreach($earn_key as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span> <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div></center>@endforeach
                      </td>
                      @endforeach
                    @elseif(count($value->Deductions) >= 21 && count($value->Deductions) <=30)
                      @foreach(array_chunk($value->Deductions, 10) as $earn_key)
                      <td class="main borders-td " style="width: 131.2px">  @foreach($earn_key as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span> <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div></center>@endforeach
                      </td>
                      @endforeach
                    @elseif(count($value->Deductions) >= 31 )
                      @foreach(array_chunk($value->Deductions, 15) as $earn_key)
                      <td class="main borders-td " style="width: 131.2px">  @foreach($earn_key as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span> <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div></center>@endforeach
                      </td>
                      @endforeach
                    @else
                      <td class="main borders-td " style="width: 50em">  @foreach($value->Deductions as $earning => $earn)<center style="border-top: .1px solid #E7E7E7; padding: 3px 0px"> <span style="color: #B6BBC2; ">{{ $earn->title}}</span> <span >  {{ number_format($earn->amount, 2,'.',',')}}</span></center>@endforeach
                      </td>
                    @endif

                    @if(count($value->Deductions) >= 1)
                      <td class="main borders-td " style="width: 81.5px"><center> {{ number_format($total,2,'.',',') }}</center> </td>
                    @else
                      <td class="main borders-td " style="width: 81.5px"><center> 0.00 </center></td>
                    @endif

                    <td class="main borders-td ">  <center>{{ number_format($value->pay_structure->net_taxable_pay, 2, '.', ',') }}</center> </td>
                    <td class="main borders-td "  style="width: 100px">  <center> {{ $value->name->last_name }}, {{ $value->name->first_name }} {{ $value->name->middle_name }}.</center> </td>
                </tr>
            </table>
        @endforeach

        <br/>
        <div>
            <table>
                <tr>
                    <td style="width: 100%">
                     <center class="header-name">
                        <strong class="label1">Total (1 to {{ count($employee_chunk)}})</strong><br/>
                     </center>
                    </td>
                </tr>
            </table>

            <table style="width; 100%; border-collapse: collapse;">
                  <tr>
                      <td class="main borders-td" style="width: 100px; padding: 10 0"> <center>Monthly Salary</center></td>
                      <td class="main borders-td" style="width: 172px">
                          <center> E A R N I N G S</center>
                      </td>
                      <td class="main borders-td" style="width: 100px"> <center> Gross Amount </center></td>
                      <td class="main borders-td" style="width: 50em" > <center> D E D U C T I O N S </center></td>
                      <td class="main borders-td" style="width: 100px"><center> Total Deduction</center></td>
                      <td class="main borders-td" style="width: 100px">  <center>  NET AMOUNT</center> </td>
                  </tr>
            </table>

            <table style="width; 100%; border-collapse: collapse;">
                @php
                  $total = 0;
                  $gross_pay = 0;
                  $net = 0;
                  $deductions = 0;
                  $deduction_arr = [];
                  $total_holder = 0;
                  $response = [];
                  $res = [];
                  $res_total  = [];


                  foreach($employee_chunk as $row => $val) {
                    $total += $val->pay_structure->monthlyRate;
                    $gross_pay += $val->gross_pay;
                    $net += $val->pay_structure->net_taxable_pay;

                    foreach($val->Deductions as $duct => $item){
                      $deductions += $item->amount;
                      array_push($deduction_arr, $item);
                    }
                  }

                @endphp
                  <tr>
                      <td class="main borders-td" style="width: 100px; padding: 10 0"> <center>{{number_format($total, 2,'.',',')}}</center></td>
                      <td class="main borders-td" style="width: 172px">
                          <center> 0</center>
                      </td>
                      <td class="main borders-td" style="width: 100px"> <center> {{number_format($gross_pay, 2,'.',',')}}</center></td>
                      <td class="main borders-td" style="width: 50em" > <center>0</center></td>
                      <td class="main borders-td" style="width: 100px"><center> {{number_format($deductions, 2,'.',',')}}</center></td>
                      <td class="main borders-td" style="width: 100px">  <center>  {{ number_format($net , 2, '.',',')}}</center> </td>
                  </tr>
            </table>

            </div>

    </div>
    @endforeach
    </body>
</html>

