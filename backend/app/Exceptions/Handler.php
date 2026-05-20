<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if (!$request->expectsJson() && !$request->is('api/*')) {
            return parent::render($request, $e);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        return new JsonResponse([
            'message' => $status >= 500 ? 'Server error.' : $e->getMessage(),
        ], $status);
    }
}
