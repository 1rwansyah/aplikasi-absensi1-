<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdfServiceClient
{
    /**
     * PDF Service endpoint URL
     */
    private string $serviceUrl;

    /**
     * PDF Service authentication token
     */
    private string $token;

    /**
     * Timeout for HTTP requests (in seconds)
     */
    private int $timeout;

    /**
     * Default PDF options
     */
    private array $defaultOptions;

    public function __construct()
    {
        $this->serviceUrl = config('pdf.url');
        $this->token = config('pdf.token', '');
        $this->timeout = config('pdf.timeout', 30);
        $this->defaultOptions = config('pdf.default_options', []);
    }

    /**
     * Generate PDF from HTML by sending to PDF Service
     *
     * @param  string  $html  HTML content to convert to PDF
     * @param  array  $options  PDF generation options
     * @return string  Binary PDF content
     * @throws \Exception
     */
    public function generatePdf(string $html, array $options = []): string
    {
        try {
            $defaultOptions = config('pdf.default_options', []);
            $mergedOptions = array_merge($defaultOptions, $options);

            $headers = [
                'Accept' => 'application/pdf',
            ];

            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
            }

            // Map options to API contract format
            $requestBody = [
                'html' => $html,
                'paper' => $mergedOptions['format'] ?? 'A4',
                'orientation' => 'portrait', // Default to portrait
            ];

            if (isset($options['filename'])) {
                $requestBody['filename'] = $options['filename'];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->serviceUrl . '/pdf/generate-pdf', $requestBody);

            if ($response->failed()) {
                $this->handleErrorResponse($response);
            }

            // Return binary PDF content
            return $response->body();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PDF Service connection failed: ' . $e->getMessage(), [
                'service_url' => $this->serviceUrl,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Gagal terhubung ke PDF Service. Pastikan service berjalan.');
        } catch (\Exception $e) {
            Log::error('PDF generation failed (PDF Service): ' . $e->getMessage(), [
                'service_url' => $this->serviceUrl,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle error response from PDF Service
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @throws \Exception
     */
    private function handleErrorResponse($response): void
    {
        $status = $response->status();
        $body = $response->body();
        
        // Try to parse JSON error response
        $errorData = json_decode($body, true);
        $errorCode = $errorData['code'] ?? 'UNKNOWN_ERROR';
        $errorMessage = $errorData['error'] ?? 'Unknown error';
        $errorDetails = $errorData['details'] ?? null;

        // Map error codes to user-friendly messages
        $errorMessages = [
            'UNAUTHORIZED' => 'Token autentikasi tidak valid atau tidak disediakan.',
            'INVALID_HTML' => 'Konten HTML tidak valid atau kosong.',
            'PAYLOAD_TOO_LARGE' => 'Ukuran HTML terlalu besar. Maksimum 10MB.',
            'BROWSER_LAUNCH_FAILED' => 'Gagal menjalankan browser. Service mungkin tidak tersedia.',
            'CONTENT_LOAD_TIMEOUT' => 'Timeout saat memuat konten HTML. Halaman terlalu lama dimuat.',
            'PDF_GENERATION_FAILED' => 'Gagal generate PDF dari konten HTML.',
            'INTERNAL_ERROR' => 'Terjadi kesalahan internal pada PDF Service.',
        ];

        $userMessage = $errorMessages[$errorCode] ?? $errorMessage;

        Log::error('PDF Service error response', [
            'status' => $status,
            'code' => $errorCode,
            'message' => $errorMessage,
            'details' => $errorDetails,
            'service_url' => $this->serviceUrl,
        ]);

        throw new \Exception($userMessage, $status);
    }

    /**
     * Check if PDF Service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $headers = [];
            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
            }

            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->get($this->serviceUrl . '/pdf/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
