<table>
    <thead>
        <tr>
            <th>No</th>
            <th>No. JP</th>
            <th>Nama Klien</th>
            <th>PIC</th>
            <th>Batch Upload</th>
            <th>Kloter Closing</th>
            <th>Tipe Dokumen</th>
            <th>Status Download</th>
            <th>Waktu Download</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($collection as $index => $item)
            @php
                $itemArray = (array)$item;
                $status = $itemArray['status'] ?? 'not_downloaded';
                $pic = $itemArray['pic'] ?? null;
                $source_file_name = $itemArray['source_file_name'] ?? null;
                $client_closing_batch_number = $itemArray['client_closing_batch_number'] ?? null;
                $document_type = $itemArray['document_type'] ?? null;
                $last_downloaded_at = $itemArray['last_downloaded_at'] ?? null;
                $no_jp = $itemArray['no_jp'] ?? null;
                $name = $itemArray['name'] ?? '';

                $statusText = match($status) {
                    'downloaded' => 'Mendownload',
                    'seeded' => 'Downloaded*',
                    default => 'Belum Mendownload'
                };
                $statusStyle = match($status) {
                    'downloaded' => 'background-color: #C3E6CB; color: #155724;',
                    'seeded' => 'background-color: #FFEEBA; color: #856404;',
                    default => 'background-color: #F5C6CB; color: #721C24;'
                };
                $picLabel = $pic ? strtoupper($pic) : '-';
                $batchLabel = $source_file_name ?? '-';
                $kloterLabel = $client_closing_batch_number ? 'Kloter ' . $client_closing_batch_number : '-';
                $docTypeLabel = ($request['documentType'] ?? 'all') === 'all' ? 'General (Ichijikin, Resi, Kwitansi)' : ucfirst($document_type);
                $timestampLabel = $last_downloaded_at ? \Carbon\Carbon::parse($last_downloaded_at)->format('Y-m-d H:i:s') : '-';
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $no_jp ?? '-' }}</td>
                <td>{{ $name }}</td>
                <td>{{ $picLabel }}</td>
                <td>{{ $batchLabel }}</td>
                <td>{{ $kloterLabel }}</td>
                <td>{{ $docTypeLabel }}</td>
                <td style="{{ $statusStyle }}">{{ $statusText }}</td>
                <td>{{ $timestampLabel }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 15px; font-size: 11px; color: #555;">
    <p><strong>Keterangan:</strong></p>
    <p>* Status <strong>Downloaded*</strong> (kuning) menunjukkan data historis/seeder dan bukan unduhan live-tracked.</p>
</div>
