<table border="1">
    <thead>
        <tr>
            <th>NO.</th>
            <th>EMPLOYEE NAME</th>
            <th>PERIOD COVERED</th>
            <th>YEAR</th>
            <th>OR NO.</th>
            <th>OR DATE</th>
            <th>AMOUNT</th>
            <th>REMARKS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sheet->getCertificates() as $certificate_index => $certificate)
            @foreach($certificate->getDetails() as $detail_index => $detail)
            <tr>
                @if($detail_index == 0)
                <td rowspan="{{ $certificate->getDetailCount() }}">{{ $certificate_index + 1 }}</td>
                <td rowspan="{{ $certificate->getDetailCount() }}">{{ $certificate->getEmployeeName() }}</td>
                @endif
                <td>{{ \Carbon\Carbon::create()->month($detail->getPeriodMonth())->monthName }}</td>
                @if($detail->displayYear())
                <td rowspan="{{ $detail->detailsWithSameYear()->count() }}">{{ $detail->getPeriodYear() }}</td>
                @endif
                <td class="text-left">{{ $detail->getORNumber() }}</td>
                <td>{{ $detail->getORDate() ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($detail->getORDate()) : "" }}</td>
                <td class="text-right">{{ $detail->getAmount() }}</td>
                <td>{{ $detail->getRemarks() }}</td>
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
