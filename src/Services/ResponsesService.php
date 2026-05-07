<?php
namespace App\Services;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ResponsesService {
    public function successResponse(?array $data = [], string $message = "Successfully requested", int $status = 1): JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'message' => $message,
            'body' => $data
        ]);
    }

    public function errorResponse(string $message = "An error occurred", int $status = 0, ?array $data = [], int $code = 200): JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'message' => $message,
            'body' => $data
        ], $code);
    }


}