<?php

namespace App\Services;

use App\Models\Client\Client;

class DocumentMatchingService
{
    /**
     * Normalize a given string (filename or client name).
     */
    public function normalize(string $name, bool $isFilename = false): string
    {
        if ($isFilename) {
            // Remove extension first
            $name = pathinfo($name, PATHINFO_FILENAME);
        }

        // Lowercase all characters
        $name = mb_strtolower($name, 'UTF-8');

        // Replace underscore, dash, dot with space
        $name = str_replace(['_', '-', '.'], ' ', $name);

        // Trim and replace multiple spaces with single space
        return preg_replace('/\s+/', ' ', trim($name));
    }

    /**
     * Extract only the candidate client name from a given filename based on its document type.
     */
    private function extractNameFromFilename(string $filename, string $type): string
    {
        $filenameNoExt = pathinfo($filename, PATHINFO_FILENAME);
        
        // Split filename by underscores or spaces using preg_split
        $tokens = preg_split('/[_\s]+/', $filenameNoExt);
        $tokens = array_filter($tokens); // remove any empty tokens

        if (empty($tokens)) {
            return '';
        }

        // Re-index array values to ensure contiguous numerical keys
        $tokens = array_values($tokens);

        switch (strtolower($type)) {
            case 'ichijikin':
                // Pola: {angka}_{NAMA LENGKAP}.jpg
                // 1. Hapus token pertama jika berupa angka murni
                if (ctype_digit($tokens[0]) || preg_match('/^\d+$/', $tokens[0])) {
                    array_shift($tokens);
                }
                break;

            case 'resi':
                // Pola: {kode}_{BANK}_{NAMA LENGKAP}_{angka}.jpg
                // 1. Hapus token pertama (kode)
                array_shift($tokens);
                
                // 2. Hapus token kedua (nama bank — deteksi: semua huruf, panjang <= 5)
                if (!empty($tokens)) {
                    if (preg_match('/^[a-zA-Z]+$/', $tokens[0]) && strlen($tokens[0]) <= 5) {
                        array_shift($tokens);
                    }
                }
                
                // 3. Hapus token terakhir jika berupa angka murni
                if (!empty($tokens)) {
                    $last = end($tokens);
                    if (ctype_digit($last) || preg_match('/^\d+$/', $last)) {
                        array_pop($tokens);
                    }
                }
                break;

            case 'kwitansi':
                // Pola: {angka}_{tanggal}_{NAMA LENGKAP}_NENKIN_{tipe}.jpg
                // 1. Hapus token pertama jika berupa angka murni
                if (ctype_digit($tokens[0]) || preg_match('/^\d+$/', $tokens[0])) {
                    array_shift($tokens);
                }
                
                // 2. Hapus token yang memiliki format tanggal (DD-MM-YYYY atau DD-MM-YY)
                $tokens = array_values(array_filter($tokens, function($token) {
                    return !preg_match('/^\d{2}-\d{2}-\d{2,4}$/', $token);
                }));
                
                // 3. Ambil token-token sebelum keyword "NENKIN" (case-insensitive)
                $nenkinIndex = null;
                foreach ($tokens as $index => $token) {
                    if (strcasecmp($token, 'NENKIN') === 0) {
                        $nenkinIndex = $index;
                        break;
                    }
                }
                if ($nenkinIndex !== null) {
                    $tokens = array_slice($tokens, 0, $nenkinIndex);
                }
                break;
        }

        return implode(' ', $tokens);
    }

    /**
     * Match a filename against clients in the database.
     * Returns an array with match information:
     * [
     *     'status' => 'matched' | 'low_confidence' | 'ambiguous' | 'failed',
     *     'confidence' => float,
     *     'matched_client' => Client|null,
     *     'candidates' => [Client, ...],
     *     'best_candidate' => Client|null
     * ]
     */
    public function matchFilename(string $filename, ?array $allowedClientIds = null, ?string $type = null): array
    {
        $isFallback = false;
        $extractedName = '';

        if ($type !== null) {
            $extractedName = $this->extractNameFromFilename($filename, $type);
        }

        if (empty(trim($extractedName))) {
            $isFallback = true;
            // Fallback: normalize entire filename
            $filenameNormalized = $this->normalize($filename, true);
        } else {
            // Normalize the extracted candidate name
            $filenameNormalized = $this->normalize($extractedName, false);
        }

        // Fetch all active clients or filter by allowed IDs
        $clients = $allowedClientIds !== null 
            ? Client::whereIn('id', $allowedClientIds)->get() 
            : Client::all();

        // 1. Find all exact matches
        $exactMatches = [];
        foreach ($clients as $client) {
            $clientNameNormalized = $this->normalize($client->name, false);
            if ($filenameNormalized === $clientNameNormalized) {
                $exactMatches[] = $client;
            }
        }

        // 2. Ambiguity Detection (Exact match found > 1 client)
        if (count($exactMatches) > 1) {
            return [
                'status' => 'ambiguous',
                'confidence' => 1.000,
                'matched_client' => null,
                'candidates' => $exactMatches,
                'best_candidate' => null,
            ];
        }

        // 3. Exact Match Found == 1 client
        if (count($exactMatches) === 1) {
            $matchedClient = $exactMatches[0];
            $confidence = $isFallback ? 0.850 : 1.000;

            return [
                'status' => $confidence >= 0.95 ? 'matched' : 'low_confidence',
                'confidence' => $confidence,
                'matched_client' => $confidence >= 0.95 ? $matchedClient : null,
                'candidates' => [],
                'best_candidate' => $confidence < 0.95 ? $matchedClient : null,
            ];
        }

        // 4. No Exact Matches -> Perform Fuzzy Matching using similar_text
        $bestMatchClient = null;
        $bestMatchScore = 0.0;

        foreach ($clients as $client) {
            $clientNameNormalized = $this->normalize($client->name, false);

            $lenA = strlen($filenameNormalized);
            $lenB = strlen($clientNameNormalized);
            $maxLen = max($lenA, $lenB);

            if ($maxLen > 0) {
                $similarChars = similar_text($filenameNormalized, $clientNameNormalized);
                $score = $similarChars / $maxLen;
            } else {
                $score = 0.0;
            }

            if ($isFallback) {
                $score = min(0.850, $score); // Cap fallback scores to maximum 0.85
            }

            if ($score > $bestMatchScore) {
                $bestMatchScore = $score;
                $bestMatchClient = $client;
            }
        }

        // Round score to 3 decimals
        $bestMatchScore = round($bestMatchScore, 3);

        if ($bestMatchScore >= 0.95) {
            return [
                'status' => 'matched',
                'confidence' => $bestMatchScore,
                'matched_client' => $bestMatchClient,
                'candidates' => [],
                'best_candidate' => null,
            ];
        } elseif ($bestMatchScore >= 0.85) {
            return [
                'status' => 'low_confidence',
                'confidence' => $bestMatchScore,
                'matched_client' => null,
                'candidates' => [],
                'best_candidate' => $bestMatchClient,
            ];
        } else {
            return [
                'status' => 'failed',
                'confidence' => $bestMatchScore,
                'matched_client' => null,
                'candidates' => [],
                'best_candidate' => $bestMatchClient,
            ];
        }
    }
}
