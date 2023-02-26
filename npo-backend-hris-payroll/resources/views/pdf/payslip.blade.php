<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
    table {
        border-left: 0.01em solid black;
        border-right: 0.01em solid black;
        border-top: 0.01em solid black;
        border-bottom: 0.01em solid black;
        width: 100%;
        padding: 4
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

    .span_label {
        color: #000;
        font-size: 14px;
    }

    .span_value {
        font-size: 14px;
        color: #000;
    }

    .bold {
        font-weight: bold;
    }

    .amount {
        text-align: right;
        padding-right: 50px;
    }

    .amount_no_padding {
        text-align: right;
    }

    td {
        vertical-align: top;
    }
    div.page_break + div.page_break{
        page-break-before: always;
    }
</style>

<body>
    @php
        $signatory = $vals['signatory'];
        $ROWS_PER_COL = 5;
    @endphp
    @foreach($vals['data'] as $value)
    <div class="page_break">
        <div>
            <table>
                <tbody>
                    <tr>
                        <td style="width: 10%; "><img src="{{ public_path() . $vals['image'] }}" style="height: 40px; width: 40px; margin-left: 5px; margin-right: 0; padding: 0 0" /></td>
                        <td style="width: 60%; "><b style=" font-size: 14px; ">National Printing Office</b><br /><span class="span_label" style=" font-size: 12px">Employee no: {{ $value['id_number']}}</span></td>
                        <td style="width: 30%; margin-left: 50px">
                            <span class="span_label" style=" font-size: 12px"><b style=" font-size: 14px; ">&nbsp;</b> <br />
                                Pay period: <span style=""> {{$vals['pay_date']}} </span></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <table>
                <tbody>
                    <tr>
                        <td style="width: 50%">
                            <span class="span_label"> Name: <span class="span_value bold"> {{ $value['first_name'] }} {{ $value['middle_name'] }} {{ $value['last_name'] }}</span> </span>
                            <br />
                            <span class="span_label">Basic salary: <span class="span_value"> {{ number_format($value['basic_pay'], 2) }} </span> </span>
                        </td>
                        <td style="width: 50%">
                            <span class="span_label">Division: <span class="span_value"> {{ $value['department_name'] }} </span></span>
                            <br />
                            <span class="span_label">Position: <span class="span_value"> {{ $value['position_name'] }} </span> </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


        <div>
            <table>
                <tr>
                    <td width="70%">
                        <table style="width: 100%; border: none; padding: 0px; ">
                            <tr>
                                <td class="span_label" style="width: 60%"> Earnings/Reimbursements: </td>
                                <td class="span_label amount_no_padding">{{ number_format( $value['gross_pay'], 2)}}</td>
                            </tr>
                            <tr>
                                <td class="span_label" style="width: 60%"> Deductions/Contributions/Loans: </td>
                                <td class="span_label amount_no_padding">{{ number_format( $value['total_deduction'], 2)}}</td>
                            </tr>
                            <tr>
                                <td class="span_label bold" style="width: 60%; border-top: 1px solid black;">NET PAY: </td>
                                <td class="span_label amount_no_padding bold" style="border-top: 1px solid black;">{{ number_format( $value['net_pay'], 2)}}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="30%"></td>
                </tr>
            </table>
        </div>


        <div style="background-color: #e1e1e1;  padding: 4; font-size: 13px; border: 1px solid black;">
            <span style="margin-left:9px">EARNINGS</span>
        </div>

        <div>
            <table>
                <tr>
                    @foreach(array_chunk($value['earnings'], $ROWS_PER_COL) as $earn_key)
                    <td width="50%">
                        <table style="width: 100%; border: none; padding: 0px; ">
                        @foreach($earn_key as $earn_row => $val)
                        <tr>
                            <td class="span_label" style="width: 60%">{{ $val['short_name'] ?? $val['title'] }}</td>
                            @php
                                $amount = $val['amount'] > 0 ? number_format( $val['amount'], 2) : '-';
                            @endphp
                            <td class="span_label amount">{{ $amount }}</td>
                        </tr>
                        @endforeach
                        </table>
                    </td>
                    @endforeach
                    @if (sizeof($value['earnings']) <= $ROWS_PER_COL)
                        <td width="50%"></td>
                    @endif
                </tr>
            </table>
        </div>
        <div style="background-color: #e1e1e1;  padding: 4; font-size: 13px; border: 1px solid black;">
            <span style="margin-left:9px">REIMBURSEMENTS</span>
        </div>

        <div>
            <table>
                <tr>
                    @if(empty($value['reimbursements']))
                    <td style="width: 50%;">
                        <center>
                            <span class="span_label"> --None--
                            </span>
                        </center>
                    </td>
                    @else
                        @foreach(array_chunk($value['reimbursements'], $ROWS_PER_COL ) as $earn_key)
                            <td width="50%">
                                <table style="width: 100%; border: none; padding: 0px; ">
                                @foreach($earn_key as $earn_row => $val)
                                <tr>
                                    <td class="span_label" style="width: 60%">{{ $val['short_name'] ?? $val['title'] }}</td>
                                    @php
                                        $amount = $val['amount'] > 0 ? number_format( $val['amount'], 2) : '-';
                                    @endphp
                                    <td class="span_label amount">{{ $amount }}</td>
                                </tr>
                                @endforeach
                                </table>
                            </td>
                        @endforeach
                        @if (sizeof($value['reimbursements']) <= $ROWS_PER_COL)
                            <td width="50%"></td>
                        @endif
                    @endif
                </tr>
            </table>
        </div>

        <div style="background-color: #e1e1e1;  padding: 4; font-size: 13px; border: 1px solid black;">
            <span style="margin-left:9px">DEDUCTIONS</span>
        </div>

        <div>
            <table>
                <tr>
                    @if(empty($value['deductions']))
                        <td style="width: 50%;">
                            <center>
                                <span class="span_label"> --None--
                                </span>
                            </center>
                        </td>
                    @else
                        @foreach(array_chunk($value['deductions'], $ROWS_PER_COL) as $earn_key)
                            <td width="50%">
                                <table style="width: 100%; border: none; padding: 0px; ">
                                @foreach($earn_key as $earn_row => $val)
                                <tr>
                                    <td class="span_label" style="width: 60%">{{ $val['short_name'] ?? $val['title'] }}</td>
                                    @php
                                        $amount = $val['amount'] > 0 ? number_format( $val['amount'], 2) : '-';
                                    @endphp
                                    <td class="span_label amount">{{ $amount }}</td>
                                </tr>
                                @endforeach
                                </table>
                            </td>
                        @endforeach
                        @if (sizeof($value['deductions']) <= $ROWS_PER_COL)
                            <td width="50%"></td>
                        @endif
                    @endif
                </tr>
            </table>
        </div>

        <div style="background-color: #e1e1e1;  padding: 4; font-size: 13px; border: 1px solid black;">
            <span style="margin-left:9px">CONTRIBUTIONS</span>
        </div>
        <div>
            <table>
                <tr>
                    @if(empty($value['contributions']))
                        <td style="width: 50%;">
                            <center>
                                <span class="span_label"> --None--
                                </span>
                            </center>
                        </td>
                    @else
                        @foreach(array_chunk($value['contributions'], $ROWS_PER_COL ) as $earn_key)
                            <td width="50%">
                                <table style="width: 100%; border: none; padding: 0px; ">
                                @foreach($earn_key as $earn_row => $val)
                                <tr>
                                    <td class="span_label" style="width: 60%">{{ $val['short_name'] ?? $val['title'] }}</td>
                                    @php
                                        $amount = $val['amount'] > 0 ? number_format( $val['amount'], 2) : '-';
                                    @endphp
                                    <td class="span_label amount">{{ $amount }}</td>
                                </tr>
                                @endforeach
                                </table>
                            </td>
                        @endforeach
                        @if (sizeof($value['contributions']) <= $ROWS_PER_COL)
                            <td width="50%"></td>
                        @endif
                    @endif
                </tr>
            </table>
        </div>

        <div style="background-color: #e1e1e1;  padding: 4; font-size: 13px; border: 1px solid black;">
            <span style="margin-left:9px">LOANS</span>
        </div>
        <div>
            <table>
                <tr>
                    @if(empty($value['loans']))
                        <td style="width: 50%;">
                            <center>
                                <span class="span_label"> --None--
                                </span>
                            </center>
                        </td>
                    @else
                        @foreach(array_chunk($value['loans'], $ROWS_PER_COL ) as $earn_key)
                            <td width="50%">
                                <table style="width: 100%; border: none; padding: 0px; ">
                                @foreach($earn_key as $earn_row => $val)
                                <tr>
                                    <td class="span_label" style="width: 60%">{{ $val['short_name'] ?? $val['title'] }}</td>
                                    @php
                                        $amount = $val['amount'] > 0 ? number_format( $val['amount'], 2) : '-';
                                    @endphp
                                    <td class="span_label amount">{{ $amount }}</td>
                                </tr>
                                @endforeach
                                </table>
                            </td>
                        @endforeach
                        @if (sizeof($value['loans']) <= $ROWS_PER_COL)
                            <td width="50%"></td>
                        @endif
                    @endif
                </tr>
            </table>
        </div>


        <div>
            <table>
                <tr>
                    <td></td>
                    <td>
                        <br />
                        <center><span class="span_label"> Authenticated by</span></center>
                        <br />
                        <br />
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td width="30%"></td>
                    <td style="border-top: 1px solid black">
                        <center><b><span class="span_label"> {{$signatory->signatories[0]['name'] ?? '<Name>'}}</span></b> <br />
                        <span class="span_label"> {{$signatory->signatories[0]['title'] ?? '<Title>' }}</span></center>
                        </td>
                    <td width="30%"></td>
                </tr>

            </table>
        </div>
    </div>
    @endforeach
</body>

</html>
