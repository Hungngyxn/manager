<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\QueryException;
use PDOException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof PDOException || $exception instanceof QueryException) {
            if (str_contains($exception->getMessage(), 'SQLSTATE[HY000] [2002]')) {
                // Chuyển hướng đến trang 404
                return response()->view('errors.500-db', [], 500);
            }
        }

        return parent::render($request, $exception);
    }
}
