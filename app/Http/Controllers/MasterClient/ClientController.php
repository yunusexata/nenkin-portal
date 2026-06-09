<?php

namespace App\Http\Controllers\MasterClient;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class ClientController extends Controller
{
    public function form_confirmation()
    {

        return view('app.client.form-confirmation');
    }
    public function document()
    {
        $user = auth()->user();

        if ($user && $user->hasRole(\App\Models\User::ROLE_CLIENT)) {
            $clientId = $user->client_id;

            // Retrieve documents for this client keyed by their type
            $documents = \App\Models\Document::where('client_id', $clientId)
                ->get()
                ->keyBy('type');

            return view('app.client.document', compact('documents'));
        }
        return view('app.client.document');
    }
    public function agreement()
    {
        $user = auth()->user();

        if ($user && $user->hasRole(\App\Models\User::ROLE_CLIENT)) {
            $clientId = $user->client_id;

            // Retrieve documents for this client keyed by their type
            $documents = \App\Models\Document::where('client_id', $clientId)
                ->get()
                ->keyBy('type');

            return view('app.client.agreement', compact('documents'));
        }
        return view('app.client.agreement');
    }

    public function create(Request $request)
    {
        return view('app.client.create', ["token" => $request->token]);
    }



    // $this->drawLabelPdf(, 'result', $user);
    public function download()
    {
        $imagePath = storage_path('app/public/template_perjanjian.pdf');
        $model = auth()->user();
        $desPath = 'result';
        $pdf = new Fpdi();

        $pageWidth = 1130;
        $pageHeight = 1680;

        $pdf->AddPage('P', [$pageWidth, $pageHeight]);

        $pdf->setSourceFile($imagePath);

        $template = $pdf->importPage(1);

        $pdf->useTemplate(
            $template,
            0,
            0,
            $pageWidth,
            $pageHeight
        );

        $pdf->SetFont('Times', '', 50);

        // NO SURAT
        $pdf->Text(515, 300, str_pad($model->client_id, 4, '0', STR_PAD_LEFT));
        // HARI
        $pdf->Text(450, 356, Carbon::parse($model->client->esign->signed_at)->locale('id')
            ->translatedFormat('l'));
        $pdf->Text(630, 356, Carbon::parse($model->client->esign->signed_at)->locale('id')
            ->translatedFormat('d F Y'));
        // NO Nama
        $pdf->Text(585, 440, $model->name);
        // NIK
        $pdf->Text(585, 462, $model->client->esign->nik);
        // Tgl Lahir
        $pdf->Text(585, 484, Carbon::parse($model->client->bod)->locale('id')
            ->translatedFormat('d F Y'));
        // Alamat
        $address = 'Perumahan Grand Permata Blok B2 No. 12, RT 04 / RW 08, Kelurahan Sukamaju, Kecamatan Cibeureum, Kota Bandung, Jawa Barat, 40212 Perumahan Grand Permata Blok B2 No. 12, RT 04 / RW 08, Kelurahan Sukamaju, Kecamatan Cibeureum, Kota Bandung, Jawa Barat, 40212';
        $split = str_split($address, 40);
        $split2 = str_split($address, 50);

        $lines = [
            $split[0],
            $split2[1],
            $split2[2],
        ];
        $y = 506;

        foreach ($lines as $index => $line) {
            if ($index < 3) {
                if ($index) {
                    $x = 500;
                } else {
                    $x = 585;
                }
                $pdf->Text($x, $y, $line);
                $y += 22; // line spacing
            }
        }

        $signature = $model->client->esign->signature_data;

        // Remove data URI prefix
        $signature = preg_replace(
            '/^data:image\/\w+;base64,/',
            '',
            $signature
        );

        // Create temporary file
        $tempFile = storage_path('app/temp-signature-' . uniqid() . '.png');


        $destFolder = storage_path('app/public/' . $desPath);

        // Make sure folder exists
        if (!file_exists($destFolder)) {
            mkdir($destFolder, 0755, true);
        }
        $outputFolder = storage_path('app/public/' . $desPath);

        if (!is_dir($outputFolder)) {
            mkdir($outputFolder, 0755, true);
        }

        file_put_contents(
            $tempFile,
            base64_decode($signature)
        );

        // Insert image into PDF
        $pdf->Image(
            $tempFile,
            670, // X
            1450, // Y
            200, // Width
            80   // Height
        );

        // Delete temp file
        unlink($tempFile);

        // NO Nama
        $pdf->SetFont('Times', '', 50);

        $leftX = 610;
        $rightX = 940;

        $text = strtoupper($model->name);

        $lines = str_split($text, 38);


        $y = 1560;
        foreach ($lines as $index => $line) {
            if ($index < 3) {

                $textWidth = $pdf->GetStringWidth($line);

                $x = $leftX + (($rightX - $leftX) - $textWidth) / 2;
                $pdf->Text($x, $y, $line);
                $y += 22; // line spacing
            }
        }

        // Save the PDF
        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="Surat Perjanjian.pdf"'
            );
    }
}
