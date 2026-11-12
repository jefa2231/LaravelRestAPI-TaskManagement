<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalPostController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $url = (string) config('services.external_api.url');
        $timeout = (int) config('services.external_api.timeout', 5);
        $retries = (int) config('services.external_api.retry', 2);

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->retry(max(1, $retries), 200)
                ->get($url)
                ->throw();
        } catch (Throwable $exception) {
            Log::warning('API eksternal gagal dipanggil.', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'API eksternal tidak dapat dihubungi.',
            ], 502);
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            return response()->json([
                'message' => 'Respons API eksternal bukan JSON yang valid.',
            ], 502);
        }

        return response()->json(['data' => $payload]);
    }
}
