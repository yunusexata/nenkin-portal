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
                <th class="text-center">NAMA</th>
                <th class="text-center">PIC</th>
                <th class="text-center">Jenis Pencairan</th>
                <th class="">Berangkat dari LPK / SO / PT?</th>
                <th class="">Tahu Exata dari mana?</th>
                <th class="">Kenapa memutuskan untuk urus nenkin di Exata?</th>
                <th class="">Apakah kamu pernah dapat penawaran dari konsultan nenkin lain?</th>
                <th class="">Jika Ya, apa nama konsultan nenkin lain tersebut?'</th>
                <th class="">Jika Ya, kenapa tetap memilih Exata dan bukan konsultan nenkin lain?</th>
                <th class="">Menurut kamu, hal apa yang paling disukai dari Exata Indonesia?</th>
                <th class="">Dari segi "Pemberkasan dokumen, informasi/edukasi, dan responsibility staff kami" — apa yang harus kami perbaiki agar pelayanan sesuai harapan? </th>
                <th class="">Apakah kamu bersedia merekomendasikan Exata Indonesia ke teman/kohai?</th>
                <th class="">Jika MERCY terkenal sebagai mobil Mewah, Elegan, untuk Kaum Berkelas, dan IPHONE terkenal sebagai HP Eksklusif, Keren, Cepat, dan Kamera Bagus — maka bagaimana kamu mendeskripsikan Exata Indonesia dalam beberapa kata?</th>

            </tr>
        </thead>
        <tbody>

            @php
                $isNumberFormat = $request['type'] == App\Helpers\ExportHelper::TYPE_PDF;
            @endphp
            @if ($collection)
                @foreach ($collection as $index => $data)
                    <tr>
                        <td>{{ $data['name'] }}</td>
                        <td>{{ $data['pic'] }}</td>
                        <td>{{ $data['claim_type'] }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q1 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q2 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q3 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q4 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q5 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q6 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q7 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q8 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q9 }}</td>
                        <td>{{ $data->upodSurveyResponse->responses->q10 }}</td>

                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>

</html>
