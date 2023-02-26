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
      @foreach($registry as $chunks)

      {{-- @foreach($chunks as $chunk) --}}
        @php
          $chunk = $chunks[0];
        @endphp
        <div style="page-break-after:always;">
          <table>
            <tr>
              <td style="width: 100%">
                <center class="header-name">
                  <strong class="label1">National Printing Office</strong><br/>
                  <label class="label2">Regular Payroll Registry report</label><br/>
                  <label class="label3">{{$chunks['payroll_period']}}</label>
                </center>
              </td>
            </tr>
          </table>
          <table style="padding: 2">
            <tr>
              <td style="width: 20%" class="entries">
                Entries on: <span class="entries-value">---</span> <br/>
                Payroll by: <span class="entries-value">{{$chunks['created_by']}}</span> <br/>
                Cards by: <span class="entries-value">{{$chunks['created_by']}}</span> <br/>
              </td>
              <td style="width: 35%" class="entries">
                Payroll Name: <span class="entries-value">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$chunks['payroll_name']}}</span> <br/>
                Deduction Period: <span class="entries-value">{{$chunks['deduction']}}</span> <br/>
                Date Generated: <span class="entries-value">&nbsp;&nbsp;{{$chunks['created_at']}}</span> <br/>
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
          @foreach($chunk->entries as $employee)
            @php
              $employee = (object) $employee;
            @endphp
            <table style=" width; 100%; border-collapse: collapse;" >
              <tr>
                <td class="main borders-td " style="width: 18px"><center>{{ $employee->index }}</center></td>
                <td class="main borders-td " style="width: 100px"><center> {{ $employee->name }}.</center></td>
                <td class="main borders-td " style="width: 144px">
                    <center>{{ $employee->position }}/ SG {{ $employee->salary_grade }}</center>
                </td>
                <td class="main borders-td " style="width: 70px"> <center>{{ number_format($employee->monthly_rate, 2, '.', ',') }}</center></td>
                @php
                  $earningCount = count($employee->earnings)+ count($employee->reimbursement);
                @endphp
                @if($earningCount <= 5 && $earningCount >= 2)
                  <td class="main borders-td " style="width: 172px">
                    @foreach($employee->earnings as $earning => $earn)
                    <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                      <span style="color: #B6BBC2; ">{{ $earn->title}}</span>
                      <span  >  {{ number_format($earn->amount, 2,'.',',')}}</span>
                    </center>
                    @endforeach
                    @foreach($employee->reimbursement as $earning => $earn)
                    <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                      <span style="color: #B6BBC2; float:left">{{ $earn->title}}</span>
                      <div style="text-align: right" >  {{ number_format($earn->amount, 2,'.',',')}}</div>
                    </center>
                    @endforeach
                  </td>
                @elseif($earningCount <= 1)
                  <td class="main borders-td " style="width: 172px">
                    @foreach($employee->earnings as $earning => $earn)
                      <center style="padding: 3px 0px">
                      <span style="color: #B6BBC2; ">{{ $earn->title}}</span>
                      <span  >  {{ number_format($earn->amount, 2,'.',',')}}</span>
                    </center>
                    @endforeach
                    @foreach($employee->reimbursement as $earning => $earn)
                    <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                      <span style="color: #B6BBC2; float: left">{{ $earn->title}}</span>
                      <div style="text-align: right" >  {{ number_format($earn->amount, 2,'.',',')}}</div>
                    </center>
                    @endforeach
                  </td>
                @else
                  @foreach(array_chunk($employee->earnings, 10) as $earn_key)
                  <td class="main borders-td " style="width: 172px">
                    @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title }}</span>
                        <div style="text-align: right">  {{number_format($earn->amount, 2,'.',',')}}</div>
                      </center>
                    @endforeach
                    @foreach($employee->reimbursement as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float: left ">{{ $earn->title}}</span>
                        <div style="text-align: right" >  {{ number_format($earn->amount, 2,'.',',')}}</div>
                      </center>
                    @endforeach
                  </td>
                  @endforeach
                @endif

                <td class="main borders-td " style="width: 60px">
                  <center> {{ number_format($employee->gross_pay, 2, '.', ',') }} </center>
                </td>
                @php
                  $deductionCount = count($employee->deductions)+count($employee->loans)+count($employee->contributions) + 1 //1 for tax;
                @endphp
                @if($deductionCount >= 5 && $deductionCount <= 10)
                  <td class="main borders-td " style="width: 50em">
                    @foreach(array_chunk($employee->deductions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 198.5px"> --}}
                      @foreach($earn_key as $earning => $earn)
                        <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                          <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                        @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->loans, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->contributions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @if(isset($employee->tax))
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $employee->tax->title }}</span>
                        <div style="text-align: right">  {{ number_format($employee->tax->amount , 2,'.',',')}}</div>
                      </center>
                    @endif

                  </td>
                @elseif($deductionCount >= 11 && $deductionCount <= 15)
                  {{-- <td class="main borders-td " style="width: 131.5px">  --}}
                    <td class="main borders-td " style="width: 50em">
                    @foreach(array_chunk($employee->deductions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 131.5px">   --}}
                      @foreach($earn_key as $earning => $earn)
                        <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                          <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->loans, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->contributions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach

                    @if(isset($employee->tax))
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $employee->tax->title }}</span>
                        <div style="text-align: right">  {{ number_format($employee->tax->amount , 2,'.',',')}}</div>
                      </center>
                    @endif
                  </td>
                @elseif($deductionCount >= 16 && $deductionCount <=20)
                  {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                    <td class="main borders-td " style="width: 50em">
                    @foreach(array_chunk($employee->deductions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->loans, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->contributions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach

                    @if(isset($employee->tax))
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $employee->tax->title }}</span>
                        <div style="text-align: right">  {{ number_format($employee->tax->amount , 2,'.',',')}}</div>
                      </center>
                    @endif

                  </td>
                @elseif($deductionCount >= 21 &&$deductionCount <=30)
                  {{-- <td class="main borders-td " style="width: 131.2px">  --}}
                    <td class="main borders-td " style="width: 50em">
                    @foreach(array_chunk($employee->deductions, 10) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 131.2px"> --}}
                      @foreach($earn_key as $earning => $earn)
                        <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                          <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->loans, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->contributions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @if(isset($employee->tax))
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $employee->tax->title }}</span>
                        <div style="text-align: right">  {{ number_format($employee->tax->amount , 2,'.',',')}}</div>
                      </center>
                    @endif
                  </td>
                @elseif($deductionCount >= 31 )
                  {{-- <td class="main borders-td " style="width: 131.2px"> --}}
                    <td class="main borders-td " style="width: 50em">
                    @foreach(array_chunk($employee->deductions, 15) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 131.2px">   --}}
                      @foreach($earn_key as $earning => $earn)
                        <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                          <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->loans, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @foreach(array_chunk($employee->contributions, 5) as $earn_key)
                    {{-- <td class="main borders-td " style="width: 97.8px">   --}}
                      @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $earn->title}}</span>
                          <div style="text-align: right">  {{ number_format($earn->amount, 2,'.',',')}}</div>
                        </center>
                      @endforeach
                    {{-- </td> --}}
                    @endforeach
                    @if(isset($employee->tax))
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $employee->tax->title }}</span>
                        <div style="text-align: right">  {{ number_format($employee->tax->amount , 2,'.',',')}}</div>
                      </center>
                    @endif
                  </td>
                @else
                  <td class="main borders-td " style="width: 50em">
                    @foreach($employee->deductions as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; ">{{ $earn->title}}</span>
                        <span >  {{ number_format($earn->amount, 2,'.',',')}}</span>
                      </center>
                    @endforeach
                    @foreach($employee->loans as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; ">{{ $earn->title}}</span>
                        <span >  {{ number_format($earn->amount, 2,'.',',')}}</span>
                      </center>
                    @endforeach
                    @foreach($employee->contributions as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; ">{{ $earn->title}}</span>
                        <span >  {{ number_format($earn->amount, 2,'.',',')}}</span>
                      </center>
                    @endforeach
                    @if(isset($employee->tax))
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #B6BBC2; float:left ">{{ $employee->tax->title }}</span>
                        <div style="text-align: right">  {{ number_format($employee->tax->amount , 2,'.',',')}}</div>
                      </center>
                    @endif
                  </td>
                @endif

                @if($deductionCount >= 1)
                  <td class="main borders-td " style="width: 81.5px"><center> {{ number_format($employee->total_deductions,2,'.',',') }}</center> </td>
                @else
                  <td class="main borders-td " style="width: 81.5px"><center> 0.00 </center></td>
                @endif

                <td class="main borders-td ">  <center>{{ number_format($employee->net_pay, 2, '.', ',') }}</center> </td>
                <td class="main borders-td "  style="width: 100px">  <center> {{ $employee->name }}.</center> </td>
              </tr>
            </table>
          @endforeach
          <br/>
          <div>
            <table>
              <tr>
                <td style="width: 100%">
                  <center class="header-name">
                    <strong class="label1">Total (1 to {{ $chunk->count }})</strong><br/>
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
              <tr>
                <td class="main borders-td" style="width: 100px; padding: 10 0"> <center>{{number_format($chunk->total_monthly, 2,'.',',')}}</center></td>
                <td class="main borders-td" style="width: 172px">
                  <center> {{ number_format($chunk->total_earnings, 2,'.',',') }}</center>
                </td>
                <td class="main borders-td" style="width: 100px"> <center> {{number_format($chunk->total_gross_pay, 2,'.',',')}}</center></td>
                <td class="main borders-td" style="width: 50em" > <center>{{number_format($chunk->total_deductions, 2,'.',',')}}</center></td>
                <td class="main borders-td" style="width: 100px"><center> {{number_format($chunk->total_deductions, 2,'.',',')}}</center></td>
                <td class="main borders-td" style="width: 100px">  <center>  {{ number_format($chunk->total_net_taxable_pay , 2, '.',',')}}</center> </td>
              </tr>
            </table>
          </div>
        </div>
      {{-- @endforeach --}}
      @endforeach
    {{-- </body>
</html> commnted out because it is causing extra blank page to be rendered--}}

