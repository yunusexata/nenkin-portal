<!DOCTYPE html>
<html>

<head>
    <title>{{ $request['title'] }}</title>
    <style>
        .table-border {
            border-collapse: collapse;
            font-size: 7px;
        }

        .table-border td {
            border: 1px solid;
            padding: 3px;
        }

        .table-border th {
            border: 1px solid;
            font-weight: bold;
            padding: 3px;
        }
    </style>
</head>

<body>

    <table class="table-border" style="width: 100%">
        <thead>
            <tr>
                <th class="text-center">NO INPUT JPN</th>
                <th class="text-center">NAMA</th>
                <th class="text-center">TGL LAHIR</th>
                <th class="text-center">NAMA FB</th>
                <th class="text-center">NAMA IG</th>
                <th class="text-center">NO TELP</th>
                <th class="text-center">PIC</th>
                <th class="text-center">LPK</th>
                <th class="text-center">NOTE</th>
                <th class="text-center">JENIS PENCAIRAN</th>
                <th class="text-center">TANDA TANGAN</th>
                <th class="text-center">POSISI SAAT INI</th>
                <th class="text-center">BULAN RE ENTRY</th>
                <th class="text-center">ALASAN RE ENTRY</th>
            </tr>
        </thead>
        <tbody>

            @php
                $isNumberFormat = $request['type'] == App\Helpers\ExportHelper::TYPE_PDF;
            @endphp
            @if ($collection)
                @foreach ($collection as $index => $data)
                    <tr>
                        <td>{{ $data['no_jp'] }}</td>
                        <td>{{ $data['name'] }}</td>
                        <td>{{ $data['dob'] ? Carbon\Carbon::parse($data['dob'])->format('Ymd') : '' }}</td>
                        <td>{{ $data['facebook'] }}</td>
                        <td>{{ $data['instagram'] }}</td>
                        <td>{{ $data['phone'] }}</td>
                        <td>{{ $data['pic'] }}</td>
                        <td>{{ $data['lpk'] }}</td>
                        <td>{{ $data['note'] }}</td>
                        <td>{{ $data['claim_type'] }}</td>
                        <td>{{ $data['esign_exists'] ? 'Sudah' : 'Belum' }}</td>
                        <td>{{ $data->confirmation?->current_position }}</td>
                        <td>{{ $data->confirmation?->departure_month ? Carbon\Carbon::createFromFormat('n-Y', $data->confirmation?->departure_month.'-'.$data->confirmation?->departure_year)->locale('id')->translatedFormat('F Y') : ''; }}</td>
                        @php
                        $text = '';
                        if($data->confirmation?->current_position === App\Models\Client\ClientConfirmation::CURRENT_POSITION_JEPANG){
                            $text = 'SUDAH DIJEPANG';
                        }elseif(
                            $data->confirmation?->current_position === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                            &&
                            $data->confirmation?->back_to_jp_type === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_NO
                        ){
                            $text = 'TIDAK KEMBALI KE JEPANG';
                        }elseif(
                            $data->confirmation?->current_position === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                            &&
                            $data->confirmation?->back_to_jp_type === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_YES
                        ){
                            $text = 'AKAN KEMBALI KE JEPANG';
                        }
                        @endphp
                        <td>{{ $text }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>

</html>
