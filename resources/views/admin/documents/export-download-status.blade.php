<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">No</th>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">Client ID / No JP</th>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">Nama Klien</th>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">PIC</th>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">Batch ID &amp; Tanggal Upload</th>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">Document Type</th>
            <th style="font-weight: bold; background-color: #001B5E; color: #ffffff;">Timestamp</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($collection as $index => $item)
            @php
                $docTypeLabel = $item->document_type ? strtoupper(str_replace('_', ' ', $item->document_type)) : 'Semua Dokumen (General)';
                $batchInfo = 'Batch #' . ($item->batch_id ?? '-') . ' (' . ($item->batch_created_at ? \Carbon\Carbon::parse($item->batch_created_at)->format('Y-m-d H:i') : '-') . ')';
                $picLabel = $item->client_pic ? strtoupper($item->client_pic) : '-';
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->no_jp ?? '-' }}</td>
                <td>{{ $item->client_name }}</td>
                <td>{{ $picLabel }}</td>
                <td>{{ $batchInfo }}</td>
                <td>{{ $docTypeLabel }}</td>
                <td>{{ $item->downloaded_at ? \Carbon\Carbon::parse($item->downloaded_at)->format('Y-m-d H:i:s') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
