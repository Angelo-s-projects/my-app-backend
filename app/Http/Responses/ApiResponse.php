<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        string $code,
        mixed $data = null,
        int $status = 200,
    ): JsonResponse {
        $response = ['code' => $code];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    public static function error(
        string $code,
        int $status,
        array $errors = [],
    ): JsonResponse {
        $response = ['code' => $code];

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}