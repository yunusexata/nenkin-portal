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

        .bg-green  { background-color: #D4EDDA; }
        .bg-red    { background-color: #F8D7DA; }
        .bg-orange { background-color: #FFE0B2; }
        .bg-yellow { background-color: #FFF3CD; }
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
                <th class="text-center">STATUS EKSPOR</th>
            </tr>
        </thead>
        <tbody>

            @php
                $isNumberFormat = $request['type'] == App\Helpers\ExportHelper::TYPE_PDF;
            @endphp
            @if ($collection)
                @foreach ($collection as $index => $data)
                    @php
                        /*
                         * Colour matrix:
                         * SPEED                                           → green  #D4EDDA
                         * NORMAL + ALASAN RE ENTRY = akan kembali jepang → red    #F8D7DA
                         * NORMAL + ALASAN RE ENTRY = sudah di jepang     → orange #FFE0B2
                         * ADJUSTMENT                                      → yellow #FFF3CD
                         */
                        $rowColor = '';

                        $claimType       = $data['claim_type'] ?? '';
                        $currentPosition = $data->confirmation?->current_position;
                        $backToJpType    = $data->confirmation?->back_to_jp_type;

                        if ($claimType === App\Models\Client\Client::CLAIM_TYPE_SPEED) {
                            $rowColor = '#D4EDDA'; // hijau
                        } elseif ($claimType === App\Models\Client\Client::CLAIM_TYPE_ADJUSTMENT) {
                            $rowColor = '#FFF3CD'; // kuning
                        } elseif ($claimType === App\Models\Client\Client::CLAIM_TYPE_NORMAL) {
                            if ($currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_JEPANG) {
                                $rowColor = '#FFE0B2'; // orange — sudah di jepang
                            } elseif (
                                $currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                                && $backToJpType === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_YES
                            ) {
                                $rowColor = '#F8D7DA'; // merah — akan kembali ke jepang
                            }
                        }

                        $rowStyle = $rowColor ? "background-color: {$rowColor};" : '';

                        // Alasan re entry text
                        $alasanReEntry = '';
                        if ($currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_JEPANG) {
                            $alasanReEntry = 'SUDAH DIJEPANG';
                        } elseif (
                            $currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                            && $backToJpType === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_NO
                        ) {
                            $alasanReEntry = 'TIDAK KEMBALI KE JEPANG';
                        } elseif (
                            $currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                            && $backToJpType === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_YES
                        ) {
                            $alasanReEntry = 'AKAN KEMBALI KE JEPANG';
                        }
                    @endphp
                    <tr style="{{ $rowStyle }}">
                        <td style="{{ $rowStyle }}">{{ $data['no_jp'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['name'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['dob'] ? Carbon\Carbon::parse($data['dob'])->format('Ymd') : '' }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['facebook'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['instagram'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['phone'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['pic'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['lpk'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['note'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['claim_type'] }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['esign_exists'] ? 'Sudah' : 'Belum' }}</td>
                        <td style="{{ $rowStyle }}">{{ $data->confirmation?->current_position }}</td>
                        <td style="{{ $rowStyle }}">{{ $data->confirmation?->departure_month ? Carbon\Carbon::createFromFormat('n-Y', $data->confirmation?->departure_month.'-'.$data->confirmation?->departure_year)->locale('id')->translatedFormat('F Y') : ''; }}</td>
                        <td style="{{ $rowStyle }}">{{ $alasanReEntry }}</td>
                        <td style="{{ $rowStyle }}">{{ $data['is_exported'] ? 'EXPORTED' : 'UN-EXPORTED' }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>

</html>
