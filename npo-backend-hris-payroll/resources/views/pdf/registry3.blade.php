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
        color: #000000; /* #415A85; */
        font-size: 9px;
        opacity: 1;
      }

      .entries-value {
        letter-spacing: 1px;
        color: #000000; /* #10336E; */
        font-size: 9px;
        opacity: 1;
        margin-left: 20px;
      }

      .entries-last {
        letter-spacing: 1px;
        color: #000000; /* #676767; */
        opacity: 1;
        font-size: 7px;
      }

      .main {
        letter-spacing: 1px;
        color: #000000; /* #415A85; */
        font-size: 8px;
        opacity: 1;
      }

      .borders-td {
        border: 1px solid #ddd;
      }

      .table-bg-color {
        background-color : #EDF7FC;
      }

      .cell-border {
          border-right:  0.5px solid #ccc;
          border-bottom:  0.5px solid #ccc;
      }

      .cell-border2 {
          border-right:  0.5px solid #ccc;
          border-bottom:  none;
      }

      .cell-border3 {
          border-right:  none;
          border-bottom:  0.5px solid #ccc;
      }

      #watermark {
        opacity: 0.1;
        position: fixed;
        bottom:   4cm;
        left:     10cm;
        width:    11cm;
        height:   11cm;
        z-index:  -1000;
      }

      .blue-label {
        color: #000000; /* #415A85; */
        font-size: 8px;
        opacity: 1;
      }

      .blue-label2 {
        color:#000000; /*  #415A85; */
        font-size:8px;
        opacity: 1;
      }

    </style>
    @php
      $actual_arr = $registry[0][0];
      $earnings_breakdown = [];
      $deductions_breakdown = [];
      foreach( $actual_arr->entries as $item){
        $earnings = array_merge($item->earnings, $item->reimbursements);
        $deductions = array_merge($item->deductions, $item->loans, $item->contributions);
        if(isset($item->taxes)){
          $deductions = array_merge($deductions, $item->taxes);
        }
        foreach($earnings as $earningValue){
          if( isset($earnings_breakdown[$earningValue->title]) ){
             $earnings_breakdown[$earningValue->title] += $earningValue->amount;
          }else{
             $earnings_breakdown[$earningValue->title] = $earningValue->amount;
          }
        }
        foreach($deductions as $deductionsValue){
          if( isset($deductions_breakdown[$deductionsValue->title]) ){
             $deductions_breakdown[$deductionsValue->title] += $deductionsValue->amount;
          }else{
             $deductions_breakdown[$deductionsValue->title] = $deductionsValue->amount;
          }
        }
      }
    @endphp

    <body>
      <script type="text/php">
        if ( isset($pdf) ) {
            // OLD
            // $font = Font_Metrics::get_font("helvetica", "bold");
            // $pdf->page_text(72, 18, "{PAGE_NUM} of {PAGE_COUNT}", $font, 6, array(255,0,0));
            // v.0.7.0 and greater
            $x = 940;
            $y = 18;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->get_font("helvetica", "bold");
            $size = 6;
            $color = array(0,0,0);
            $word_space = 0.0;  //  default
            $char_space = 0.0;  //  default
            $angle = 0.0;   //  default
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
      <div id="watermark">
        <img src="{{ $logo }}" height="100%" width="100%" />
      </div>
      @foreach($registry as $chunks)
        @php
          $chunk = $chunks[0];
          function checkLenght($arr, $index, $last=false){
            if( (COUNT($arr) - 1) == $index ){
              if($last){
                return 'cell-border3';
              }
              return 'cell-border2';
            }else if($last){
              return 'cell-border3';
            }
            return 'cell-border';
          }
        @endphp
        <div style="page-break-after:always;">
          <table class="table-bg-color" >
            <tr>
              <td style="width: 100%">
                <center class="header-name">
                  <strong class="label1">National Printing Office</strong><br/>
                  <label class="label2">Regular Payroll Registry report</label><br/>
                  <label class="label3" >
                    <label style="color : #676767; font-size: 10px;">Period of</label>
                   {{$chunks['payroll_period']}}
                  </label>
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
            <table class="table-bg-color" style=" margin-top: 5px; width; 100%; border-collapse: collapse;">
              <tr>
                <td style="width: 20px; padding: 10 0" class="main borders-td"><center>NO.</center></td>
                <td class="main borders-td"  style="width: 100px"><center>Employee Name</center></td>
                <td class="main borders-td" style="width: 144px">
                    <center>Position / Salary Grade</center>
                </td>
                <td class="main borders-td" style="width: 70px"> <center>Monthly Salary</center></td>
                <td class="main borders-td" style="width: 150px">
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

                @foreach(array_chunk($employee->earnings, 5) as $earn_key)
                  <td class="main borders-td " style="width: 150px">
                    @foreach($earn_key as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #000000; float:left ">{{ $earn->title }}</span>
                        <div style="text-align: right">  {{number_format($earn->amount, 2,'.',',')}}</div>
                      </center>
                    @endforeach
                    @foreach($employee->reimbursements as $earning => $earn)
                      <center style="border-top: .1px solid #E7E7E7; padding: 3px 0px">
                        <span style="color: #000000 ; float: left ">{{ $earn->title}}</span>
                        <div style="text-align: right" >  {{ number_format($earn->amount, 2,'.',',')}}</div>
                      </center>
                    @endforeach
                  </td>
                @endforeach

                <td class="main borders-td " style="width: 60px">
                  <center> {{ number_format($employee->gross_pay, 2, '.', ',') }} </center>
                </td>
                @php
                  $deduction_arr = array_merge($employee->deductions, $employee->loans, $employee->contributions);
                  if(isset($employee->taxes)){
                    $deduction_arr = array_merge($deduction_arr, $employee->taxes);
                  }
                  $counter = 1;
                @endphp
                <td class="main borders-td " style="width: 50em">
                    <table style="border:none;" >
                      <tr>
                          @foreach(array_chunk($deduction_arr, 5) as $deduction_item)
                            @php
                              $last = false;
                              if($counter == COUNT( array_chunk($deduction_arr, 5) )) {
                                  $last = true;
                              }
                              $counter++;
                            @endphp

                            <td style="">
                              @for ($i=0; $i < COUNT($deduction_item); $i++)
                                <div class="{{ checkLenght($deduction_item, $i, $last ) }}" style="height : 20px;">
                                  <span style="color: #000000; margin-right: 5px">{{ $deduction_item[$i]->title }} </span>
                                  <span >{{ number_format( $deduction_item[$i]->amount, 2)}} </span>
                                </div>
                              @endfor
                            </td>
                          @endforeach
                      </tr>
                  </table>

                </td>

                @if(COUNT($deduction_arr) >= 1)
                  <td class="main borders-td "><center> {{ number_format($employee->total_deductions,2,'.',',') }}</center> </td>
                @else
                  <td class="main borders-td "><center> 0.00 </center></td>
                @endif

                <td class="main borders-td ">  <center>{{ number_format($employee->net_pay, 2, '.', ',') }}</center> </td>
                <td class="main borders-td "  style="width: 100px">  <center> {{ $employee->name }}.</center> </td>
              </tr>
            </table>
          @endforeach
          <br/>
        </div>
        <div style="page-break-after:always;">
            <table class="table-bg-color">
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
                  @php
                    $counter = 0;
                    $last = false;
                  @endphp

                  <table style="border:none;" >
                      <tr>
                          @foreach(array_chunk($earnings_breakdown, 5, true) as $earnings_breakdown_item)
                            @php
                              $last = false;
                              if($counter == COUNT( array_chunk($earnings_breakdown, 5, true)[$counter] )) {
                                  $last = true;
                              }
                              $counter++;
                            @endphp
                            <td>
                              @foreach ($earnings_breakdown_item as $key => $value)
                                <div class="{{ checkLenght($earnings_breakdown_item, ($counter), $last ) }} " style="height : 20px;">
                                  <span style="color: #000000; margin-right: 5px">{{ $key }} </span>
                                  <span >{{ number_format( $value, 2)}} </span>
                                </div>
                              @endforeach
                            </td>
                          @endforeach
                      </tr>
                  </table>

                </td>
                <td class="main borders-td" style="width: 100px"> <center> {{number_format($chunk->total_gross_pay, 2,'.',',')}}</center></td>
                <td class="main borders-td" style="width: 50em" >
                    @php
                      $counter = 1;
                      $last = false;
                    @endphp

                  <table style="border:none;" >
                      <tr>
                          @foreach(array_chunk($deductions_breakdown, 5, true) as $deductions_breakdown_item)
                            @php
                              if($counter == COUNT(array_chunk($deductions_breakdown, 5, true)) ) {
                                  $last = true;
                              }
                              $counter++;
                            @endphp
                            <td>
                              @foreach ($deductions_breakdown_item as $key => $value)
                                <div class="{{ checkLenght($deductions_breakdown_item, ($counter - 1), $last ) }} " style="height : 20px;">
                                  <span style="color: #000000; margin-right: 5px">{{ $key }} </span>
                                  <span >{{ number_format( $value, 2)}} </span>
                                </div>
                              @endforeach
                            </td>
                          @endforeach
                      </tr>
                  </table>
                </td>
                <td class="main borders-td" style="width: 100px"><center> {{number_format($chunk->total_deductions, 2,'.',',')}}</center></td>
                <td class="main borders-td" style="width: 100px">  <center>  {{ number_format($chunk->total_net_taxable_pay , 2, '.',',')}}</center> </td>
              </tr>
            </table>

            <div style="position : relative;" >
                <table style="border : 1px solid #ccc; border-collapse: collapse; margin : 50px 150px 0px 50px;" >
                  <tr style="border : 1px solid #ccc ;" >

                    <td class="main borders-td" style="height : 150px; width : 50%; padding : 10px; 10px;">
                        <p style= "padding : 10px 0px 0px 10px;" class="blue-label2" >
                          Certified : Services duly rendered as stated.
                        </p>
                        <p style= "margin: 0px 100px; padding : 50px 0px 8px 0px; border-bottom : 1px solid #ccc; font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[0]['name'] ?? '' }}
                        </p>
                        <p style= "font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[0]['title'] ?? '' }}
                        </p>
                    </td>
                    <td class="main borders-td" style="height : 150px; width : 50%; padding : 10px; 10px;">
                        <p style= "padding : 10px 0px 0px 10px;" class="blue-label2" >
                            Approved for :
                            <br />
                            {{$amtInWords}}
                            <span style= "margin-left : 10%">
                                {{ number_format($chunk->total_net_taxable_pay , 2, '.',',')}}
                            </span>
                        </p>
                        <p style= "margin: 0px 100px; padding : 50px 0px 8px 0px; border-bottom : 1px solid #ccc; font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[1]['name'] ?? '' }}
                        </p>
                        <p style= "font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[1]['title'] ?? '' }}
                        </p>

                    </td>

                  </tr>
                  <tr  style="border : 1px solid #ccc ;"  >
                    <td class="main borders-td" style="height : 150px; width : 50%; padding : 10px; 10px;">
                        <p style= "padding : 10px 0px 0px 10px;" class="blue-label2" >
                            Certified : Supporting documents complete and proper and cash  available in the amount of P_______________
                        </p>
                        <p style= "margin: 0px 100px; padding : 50px 0px 8px 0px; border-bottom : 1px solid #ccc; font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[2]['name'] ?? '' }}
                        </p>
                        <p style= "font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[2]['title'] ?? '' }}
                        </p>



                    </td>
                    <td class="main borders-td" style="height : 150px; width : 50%; padding : 10px; 10px;">
                        <p style= "padding : 10px 0px 0px 10px;" class="blue-label2" >
                            Certified : Each employee whose name appears above has been paid the amount indicated opposite on his/her name.
                        </p>
                        <p style= "margin: 0px 100px; padding : 50px 0px 8px 0px; border-bottom : 1px solid #ccc; font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[3]['name'] ?? '' }}
                        </p>
                        <p style= "font-size : 10px; text-align: center" class="blue-label2">
                            {{ $signatories->signatories[3]['title'] ?? '' }}
                        </p>

                    </td>

                  </tr>
                </table>
                <div style="font-size : 10px; color : #000; position: absolute; top : 60; right : 10;" >
                    ALOBS NO. : ____________
                </div>
                <div style="font-size : 10px; color : #000; position: absolute; top : 140; right : 25;" >
                    DATE : _____________
                </div>
                <div style="font-size : 10px; color : #000; position: absolute; top : 220; right : 25;" >
                    DATE : _____________
                </div>

            </div>

          </div>
      @endforeach
    {{-- </body>
</html>  --}}
{{-- commnted out because it is causing extra blank page to be rendered --}}

