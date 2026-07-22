<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function error(string $code, string $message, int $status, ?array $errors = null): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message, 'status' => $status];

        if ($errors !== null) {
            $error['errors'] = $errors;
        }

        return response()->json(['error' => $error], $status);
    }
}
