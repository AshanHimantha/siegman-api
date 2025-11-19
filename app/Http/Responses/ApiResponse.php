<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function error(string $message = 'Error', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Return an unauthorized error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Return a forbidden error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Return a not found error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Return a server error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Return a created response.
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Return a no content response.
     *
     * @return JsonResponse
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
