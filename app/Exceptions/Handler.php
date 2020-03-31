<?php

namespace App\Exceptions;

use App\Traits\ApiResponser;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    use ApiResponser;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     * @param Exception $exception
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof HttpException) {
            $code = $exception->getStatusCode();
            $message = Response::$statusTexts[$code];
            return $this->errorMessage($message, null,$code);
        }
        if ($exception instanceof ModelNotFoundException) {
            $model = strtolower(class_basename($exception->getModel()));
            return $this->errorMessage("No existe una instancia de {$model} con ese id", null,Response::HTTP_NOT_FOUND);
        }
        if ($exception instanceof AuthorizationException) {
            return $this->errorMessage($exception->getMessage(), null,Response::HTTP_FORBIDDEN);
        }
        if ($exception instanceof AuthenticationException) {
            return $this->errorMessage($exception->getMessage(), null,Response::HTTP_UNAUTHORIZED);
        }
        if ($exception instanceof ValidationException) {
            $errors = $exception->validator->errors()->getMessages();
            return $this->errorMessage($errors, null,Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (env('APP_DEBUG', false)) {
            return parent::render($request, $exception);
        }
        return $this->errorMessage('Unexpected error. Try later', null,Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
