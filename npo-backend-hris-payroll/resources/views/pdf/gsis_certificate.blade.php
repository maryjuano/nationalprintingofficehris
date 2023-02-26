<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <link href="{{ asset('css/tailwind.css') }}" rel="stylesheet"/>
    </head>

    <body class="container mx-auto text-sm">
    <div class="my-8">{{ $certificate->getDateGenerated()->format('F d, Y') }}</div>
    <div class="my-8">
        GOVERNMENT SERVICE INSURANCE SYSTEM
        <br>2302 Roxas Blvd, Pasay City
        <br>Metro Manila
    </div>
    <div class="my-8">Sir/Madam:</div>
    <div class="my-8 text-justified">
    This is to certify that the total amount of PHP {{ number_format($certificate->getTotalAmount(), 2) }} representing the Government Service Insurance System
    Educational Loan II of Mr/Mrs. {{ strtoupper($certificate->getEmployeeName()) }}, covering the period from {{ $certificate->getPeriodStart()->format('F Y') }} to {{ $certificate->getPeriodEnd()->format('F Y') }}
    were deducted from his/her salaries and remitted to your office as follows.
    </div>

    <table class="table-fixed w-full">
        <thead>
            <tr>
                <th class="w-5/12">PERIOD COVERED</th>
                <th class="w-1/12"></th>
                <th class="w-1/12 text-left">O.R. NOS</th>
                <th class="w-2/12">DATE</th>
                <th class="w-1/12 text-right">AMOUNT</th>
                <th class="w-1/12"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($certificate->getDetails() as $detail)
            <tr>
                <td>{{ \Carbon\Carbon::create()->month($detail->getPeriodMonth())->monthName }}</td>
                @if($detail->displayYear())
                <td>{{ $detail->getPeriodYear() }}</td>
                @else
                <td></td>
                @endif
                <td class="text-left">{{ $detail->getORNumber() }}</td>
                <td>{{ $detail->getORDate() ? $detail->getORDate()->format('m-d-Y') : "" }}</td>
                <td class="text-right">{{ $detail->getAmount() === null ? "" : number_format($detail->getAmount(), 2) }}</td>
                <td>{{ $detail->getYearEndTotal() !== null ? number_format($detail->getYearEndTotal(), 2) : "" }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"></td>
                <td>TOTAL</td>
                <th class="text-right">{{ number_format($certificate->getTotalAmount(), 2) }}</th>
            <tr>
        </tfoot>
    </table>

    <div>
    This certification is issued for whatever legal purposes it may serve.
    </div>

    <div>
    Very truly yours,
    </div>

    <p>
    @foreach($certificate->getSignatories() as $signatory)
    {{ $signatory->getName() }}
    <br>{{ $certificate->getPosition() }}
    @endforeach
    </p>

        
    </body>
</html>
        
