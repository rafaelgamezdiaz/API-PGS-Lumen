<?php

namespace App\Http\Middleware;

use App\Traits\ConsumesExternalService;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class Authenticate
{
    use ConsumesExternalService;
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    protected $client;
    public function __construct()
    {
        $this->client= new Client();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $headers = $this->getHeaders($request);
        try{
            $response = $this->client->get(env('USERS_SERVICE_BASE_URL') . '/us/validate',['headers' => $headers]);
        }catch (ClientException $exception){
            $response = $exception->getResponse();
            return response()->json(["error"=>true,"message"=>'Unauthorized!'],$response->getStatusCode());
        }catch (ServerException $exception){
            $response = $exception->getResponse();
            return response()->json(["error"=>true,"message"=>"Users Internal Error"],$response->getStatusCode());
        }

        $request->attributes->add(['xtimezone' => $headers['xtimezone']]);
        $request->attributes->add(['user' => json_decode($response->getBody())]);

        return  $next($request);
    }
}
