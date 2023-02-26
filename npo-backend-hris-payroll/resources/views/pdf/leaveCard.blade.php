<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>

<style type="text/css">
    @font-face {
        font-family: 'Helvetica';
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Helvetica, sans-serif;
        padding: 20 15 5 15;
        font-size: 12px;
    }

    table {
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        border-collapse: collapse;
    }

    table,
    th,
    td {
        border: 1px solid black;
    }

    th {
        font-weight: 500;
    }

    td {
        height: 40px;
    }

    .table-title-header {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .page-break {
        page-break-after: always;
    }

    .center {
        text-align: center;
    }

    .to-upper-case {
        text-transform: uppercase;
    }

    .header-font {
        font-size: 10px;
    }

    .underline {
        border-top: 2px solid #000;
        width: 70%;
        margin: 8px auto;
    }

    .bg-blue {
        background-color: #D9F4FF;
    }

    .check {
        margin: auto;
        display: inline-block;
        transform: rotate(45deg);
        height: 10px;
        width: 5px;
        border-bottom: 3px solid #000;
        border-right: 3px solid #000;
    }

</style>
@php

function formatHourMinutes($time, $type)
{
    if ($time == '00' && $type == 'minutes') {
        return '0';
    } elseif ($time == '00' && $type == 'hour') {
        return '';
    } else {
        return intval($time);
    }
}

@endphp

<body>
    @foreach ($leaveCards as $index => $leaveCard)
        <div class="page-break">
            <div>
                <table class="" style=" border: none;">
                    <tr style="border: none;">
                        <th style="border: none; width: 50%;"></th>
                        <th style="border: none; width: 50%;"></th>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;">
                            <div class="center">
                                <div class="to-upper-case">
                                    {{ $leaveCard->getFullName() }}
                                </div>
                                <div class=" center underline"></div>
                                <div class="center" style="padding-top : 5px;">
                                    ( Name )
                                </div>
                            </div>
                        </td>
                        <td style="border: none;">
                            <div class="center">
                                <div class="to-upper-case">
                                    <b>CALENDAR YEAR </b>
                                    {{ $leaveCard->getCalendarYear() }}
                                </div>
                                <div class="center underline"></div>
                                <div class="center" style="padding-top : 5px;">
                                    &nbsp;
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;" class="center">
                            <div style="position:relative;">
                                <div class="to-upper-case" style="color : red;">
                                    &nbsp;
                                </div>
                                <div class="center underline"></div>
                                <div class="center" style="padding-top : 5px;">
                                    Date of Original Appointment
                                </div>
                            </div>
                        </td>
                        <td style="border: none;" class="center">
                            <div style="position:relative;">
                                <div class="to-upper-case">
                                    <b>DIVISION </b>
                                    {{ $leaveCard->getDivision() }}
                                </div>
                                <div class="center underline"></div>
                                <div class="center" style="padding-top : 5px;">
                                    &nbsp;
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin-top : 30px;">
                <table>
                    <tr>
                        <th style="width: 61.5%;"></th>
                        <th style="width: 38.5%;"></th>
                    </tr>
                    <tr>
                        <td>
                            <div class="center">
                                <div style="margin : 3px 0">
                                    <b>TIME RECORD</b>
                                </div>
                                <div style="margin-bottom : 3px">
                                    A-Absent C-Vacation Leave E-Excused H-Half Pay
                                </div>
                                <div style="margin-bottom : 3px">
                                    I-Injured in Line of Duty L-Accrued Leave N-Night
                                </div>
                                <div style="margin-bottom : 3px">
                                    A-Sick W-Without Pay
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="center">
                                <b>Extra Compensation</b>
                            </div>
                        </td>
                    </tr>
                </table>
                <table class="table-title-header">
                    <tr>
                        @for ($i = 1; $i <= 15; $i++)
                            <th style="width : 4%; height : 35px;">{{ $i }}</th>
                        @endfor

                        <th style="width : 4%"></th>
                        <th class="header-font" style="width : 8%" rowspan="2">MONTH</th>
                        <th class="header-font" style="width : 8%" rowspan="2">WORKING DAYS RATE PER DAY</th>
                        <th class="header-font" style="width : 7%" rowspan="2">FULL DAY SERVICE</th>
                        <th class="header-font" style="width : 8%" rowspan="2">AMOUNT EARNED</th>
                        <th class="header-font" style="width : 9%" rowspan="2">OBLIGATION NUMBER</th>

                    </tr>
                    <tr>
                        @for ($i = 16; $i <= 31; $i++)
                            <th style="width : 4%; height : 35px;">{{ $i }}</th>
                        @endfor
                    </tr>
                </table>
                @foreach ($leaveCard->getMonths() as $leaveCardMonth)
                    <table>
                        <tr>
                            {{-- iterating thru 1-15 days so 15 columns --}}
                            @for ($i = 1; $i <= 15; $i++)
                                <th style="width : 4%; height : 35px;  position : 'relative'; "
                                    class={{ $leaveCardMonth->getDay($i)->isRestDayOrWeekend() ? 'bg-blue' : '' }}>
                                    <span style="font-size : 10px;"
                                        class="{{ $leaveCardMonth->getDay($i)->isCheck() ? 'check' : '' }}">
                                        {!! $leaveCardMonth->getDay($i)->getData() !!}
                                    </span>
                                </th>
                            @endfor

                            <th style="width : 4%"></th>
                            <th style="width : 8%; padding : 0px;">
                                @foreach ($leaveCardMonth->getLeaveDates() as $leaveDate)
                                    <div style="font-size : 8px; text-align : left;">
                                        {{ $leaveDate }}
                                    </div>
                                @endforeach
                            </th>
                            <th class="header-font" style="width : 8%"></th>
                            <th class="header-font" style="width : 7%"></th>
                            <th class="header-font" style="width : 5%"></th>
                            <th class="header-font" style="width : 3%"></th>
                            <th class="header-font" style="width : 9%"></th>

                        </tr>
                        {{-- 2 <tr> element since 2 rows per months --}}
                        <tr>
                            {{-- iterating thru 16-31 days so 15 columns --}}
                            @for ($i = 16; $i <= 31; $i++)
                                <th style="width : 4%; height : 35px;  position : 'relative'; "
                                    class={{ $leaveCardMonth->getDay($i)->isRestDayOrWeekend() ? 'bg-blue' : '' }}>
                                    <span style="font-size : 10px;"
                                        class="{{ $leaveCardMonth->getDay($i)->isCheck() ? 'check' : '' }}">
                                        {!! $leaveCardMonth->getDay($i)->getData() !!}
                                    </span>
                                </th>
                            @endfor
                            <th style="font-size : 9px;"> {{ $leaveCardMonth->getMonth() }} </th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </table>
                @endforeach
            </div>
        </div>

        {{-- second page --}}

        <div class="{{ $index + 1 == count($leaveCards) ? '' : 'page-break' }}">
            <div style="margin-top : 20px; padding : 0px auto; position:relative;">
                <table class="" style=" border: none;">
                    <tr style="border: none;">
                        <th style="border: none; width: 50%;"></th>
                        <th style="border: none; width: 50%;"></th>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;" class="center">
                            <div>
                                <div class="to-upper-case">
                                    <b> {{ $leaveCard->getFullName() }} </b>
                                </div>
                                <div class="center underline"></div>
                                <div class="center" style="padding-top : 5px;">
                                    ( Name )
                                </div>
                            </div>
                        </td>
                        <td style="border: none;" class="center">
                            <div>
                                <div class="to-upper-case">
                                    <b>{{ $leaveCard->getDivision() }}</b>
                                </div>
                                <div class="center underline"></div>
                                <div class="center" style="padding-top : 5px;">
                                    ( Division )
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="padding-top : 60px;">
                <table>
                    <tr>
                        <th style="width : 10%; padding : 10px;" rowspan="2"></th>
                        <th style="width : 31%; padding : 10px;" colspan="2">
                            <div style="margin-bottom :8px; ">
                                VACATION LEAVE
                            </div>
                            <div class="header-font" style="color: #585858; ">
                                BALANCE FORWARDED : ________________
                            </div>
                        </th>
                        <th style="width : 31%; padding : 10px; " colspan="2">
                            <div style="margin-bottom :8px; ">
                                SICK LEAVE
                            </div>
                            <div class="header-font" style="color: #585858; ">
                                BALANCE FORWARDED : ________________
                            </div>
                        </th>
                        <th style="width : 12%; padding : 10px;">
                            LEAVE WITHOUT PAY
                        </th>
                        <th style="width : 16%; padding : 10px;" rowspan="2">
                            REMARKS
                        </th>
                    </tr>
                    <tr>
                        <th style="width : 15%; padding : 10px;"> DAYS TAKEN </th>
                        <th style="width : 16%; padding : 10px;"> DAYS BALANCE </th>
                        <th style="width : 15%; padding : 10px;"> DAYS TAKEN </th>
                        <th style="width : 16%; padding : 10px;"> DAYS BALANCE </th>
                        <th style="width : 12%; padding : 10px;"> NUMBER OF DAYS </th>
                    </tr>

                    @foreach ($leaveCard->getMonths() as $leaveCardMonth)
                        <tr>
                            <th style="height: 86px;" rowspan="2">
                                {{ $leaveCardMonth->getMonth() }}
                            </th>
                            <th style="height: 43px;">
                                {{ $leaveCardMonth->getVLDaysTaken() }}
                            </th>
                            <th style="height: 43px;">
                                {{-- Empty --}}
                            </th>
                            <th style="height: 43px;">
                                {{-- Empty --}}
                            </th>
                            <th style="height: 43px;">
                                {{-- Empty --}}
                            </th>
                            <th style="height: 43px;">
                                {{ $leaveCardMonth->getLeavesWithoutPay() }}
                            </th>
                            <th style="height: 86px;" rowspan="2">
                                {{ $leaveCardMonth->getTotalSLAndVLBalance() }}
                            </th>
                        </tr>
                        <tr>
                            <th style="height: 43px;">{{ $leaveCardMonth->getVLPointsTaken() }}</th>
                            <th style="height: 43px;">{{ $leaveCardMonth->getVLDaysBalance() }}</th>
                            <th style="height: 43px;">{{ $leaveCardMonth->getSLDaysTaken() }}
                                {{ $leaveCardMonth->getApprovedSLText() }}</th>
                            <th style="height: 43px;">{{ $leaveCardMonth->getSLDaysBalance() }}</th>
                            <th style="height: 43px;">
                                {{-- Empty --}}
                            </th>
                        </tr>
                    @endforeach
                </table>
            </div>

        </div>
    @endforeach
</body>

</html>
