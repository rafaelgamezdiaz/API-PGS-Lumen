<?php
/**
 * Created by PhpStorm.
 * User: zippyttech
 * Date: 01/28/19
 * Time: 09:05 AM
 */
namespace App\Traits;


use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

trait ApiResponser
{

    /**
     * Build success response
     * @param $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($message, $data = null, $code = Response::HTTP_OK)
    {
        if ($data) {
            return response()->json(['message' => $message, 'data' => $data, 'status' => $code], $code)->header('Content-Type', 'application/json');
        }
        return response()->json(['message' => $message, 'status' => $code], $code)->header('Content-Type', 'application/json');
    }

    public function simpleResponse($data)
    {
        return response()->json($data)->header('Content-Type', 'application/json');
    }

    public function dataResponse($data = null, $code = Response::HTTP_OK)
    {
        return response()->json(['data' => $data], $code)->header('Content-Type', 'application/json');
    }

    /**
     * Error Response
     * @param $message
     * @param null $exception
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorMessage($message, $exception = null, $code = Response::HTTP_CONFLICT)
    {
        if ($exception !== null) {
            Log::critical($exception->getMessage() . "\n" . $exception->getFile() . "\n" . $exception->getLine());
            $message = env('APP_DEBUG') == true ? $message.': '.$exception->getMessage() : $message;
        }
        return response()->json([
            "message" => $message,
            "status" => $code
        ], $code)->header('Content-Type', 'application/json');
    }
}
