<?php
/**
 * Created by PhpStorm.
 * User: zippyttech
 * Date: 13/01/2020
 * Time: 09:05 AM
 */
namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


trait ConsumesExternalService
{

    public $baseUri;
    public $port;
    public $secret;
    public $prefix;
    private $route;

    /**
     * @return mixed
     * Get Router Driver
     */
    public function getURL()
    {
        if($this->port != ''){
            return $this->route = $this->baseUri.':'.$this->port.$this->prefix;
        }
        return $this->route = $this->baseUri.$this->prefix;
    }

    public function getHeaders($request){
        $token = '';
        if($request->hasHeader('Authorization')){
            $token = $request->header('Authorization');
        }
        if ($request->has('token')){
            $token = 'Bearer ' .$request->input('token');
        }
        return [
            "Authorization" => $token,
            "Accept" => "application/json",
            "Cache-Control" => "no-cache"
        ];
    }

    public function getToken($request){
        if($request->hasHeader('Authorization')){
            $authorization = $request->header('Authorization');
            return substr($authorization, 7, strlen($authorization));
        }
        else if ($request->has('token')){
            return $request->input('token');
        }
        return null;
    }

    /*
     * Get Router Customer
     */
    public function getRouteCustomer()
    {
        $this->getenvCustomer();
        return $this->route = $this->baseUri.':'.$this->port.$this->prefix;
    }

    /*
     * Get Router Access Control
     */
    public function getRouteAccessControl()
    {
        $this->getenvAccessControl();
        if(!empty($this->port)){
            return $this->route = $this->baseUri.':'.$this->port.$this->prefix;
        }
        return $this->route = $this->baseUri.$this->prefix;
    }

    /*
     * Get Router Sales
     */
    public function getRouteSales(){
        $this->getEnvSales();
        if(!empty($this->port)){
            return $this->route = $this->baseUri.':'.$this->port.$this->prefix;
        }
        return $this->route = $this->baseUri.$this->prefix;
    }

    /***
     * @return string
     */
    public function getRouteInventory(){
        $this->getEnvInventory();
        if(!empty($this->port)){
            return $this->route = $this->baseUri.':'.$this->port.$this->prefix;
        }
        return $this->route = $this->baseUri.$this->prefix;
    }

    public function getAccount($request){
        return $request->get('user')->user->current_account;
    }

    public function getUserName($request){
        return $request->get('user')->user->username;
    }

    /**
     * @param $token, $request
     * @return json ($fields = ['u.user_id','u.name','u.email','ur.role_id','r.authority'])
     */
    public function getAllUsers($request=null){
        $url = $this->getRouteUser().'/users';
        return $this->getUsersFromAPIUsers($url, $request);
    }

    public function getUserByField($request, $field_name, $field_value){
        $token = $this->getToken($request);
        $headers = $this->getHeaders($request);
        $url = $this->getRouteUser().'/users?where=[{"op":"eq","field":"users.'.$field_name.'","value":"'.$field_value.'"}]&token='.$token;
        //$response = $this->getUsersFromAPIUsers($url,$request);
        $resu_user = $this->performRequest('GET', $url,null,$headers);

        if( !($resu_user instanceof JsonResponse) ){
            if(count($resu_user)>0){
                if( array_key_exists( "message", $resu_user) ){
                    if ($resu_user['message'] == "no hay registros")
                    {
                        return null;
                    }
                }
                return collect($resu_user);
            }
        }
        return null;
    }

    public function getUsersFromAPIUsers($url, $request){
        $headers = $this->getHeaders($request);

        $resu_user = $this->performRequest('GET', $url,null,$headers);
        if( !($resu_user instanceof JsonResponse) ){
            if(count($resu_user)>0){
                return collect($resu_user);
            }
        }
        return null; //response()->json(['Sorry. Not users found for this request!'], 409);
    }

    public function checkHeader($request){
        if($request && $request->hasHeader('Authorization')){
            return $request->header('Authorization');
        }
        return null;    }


    public function getTokenFromHeader($request){
        if($request && $request->hasHeader('token')){
            return $request->header('token');
        }
        return null;
    }

    /**
     * Send a request to any service
     * @return string
     */
    public function performRequest($method, $requestUrl, $formParams = null, $headers =[])
    {
        $client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 20,
        ]);
        try{
            if($method=='GET'){
                $response = $client->get($requestUrl,['headers' => $headers]);
            }elseif($method=='POST'){
                if($formParams instanceof Request){
                    $response = $client->post( $requestUrl, ['json' => $formParams->all(), 'headers' => $headers]);
                }else{
                    $response = $client->post( $requestUrl, ['json' => $formParams, 'headers' => $headers]);
                }
            }elseif($method=='PUT'){
                $response = $client->put($requestUrl, ['json' => $formParams, 'headers' => $headers]);
            }else{
                //$response = $client->request($method, $requestUrl, ['json' => $formParams, 'headers' => $headers], ['connect_timeout' => 2, 'timeout' => 3, 'debug' => true]);
                $response = $client->request($method, $requestUrl,  ['json' => $formParams, 'headers' => $headers]);
            }
            //$response = $client->request($method, $requestUrl, ['json' => $formParams, 'headers' => $headers]);
            return json_decode($response->getBody(), true);

        }catch (ClientException $exception){
            Log::critical($exception->getResponse()->getBody());
            $response['status'] = false;
            $response['response'] = json_decode($exception->getResponse()->getBody());
            $response['code'] = $exception->getResponse()->getStatusCode();
            return $response;
        }catch (ServerException $exception){
            Log::critical($exception->getResponse()->getBody());
            $response['status'] = false;
            $response['response'] = json_decode($exception->getResponse()->getBody());
            $response['code'] = $exception->getResponse()->getStatusCode();
            return $response;
        }catch (GuzzleException $exception){
            Log::critical($exception->getMessage() . "\n" . $exception->getFile() . "\n" . $exception->getLine());
            $response['status'] = false;
            $response['response'] = $exception->getMessage();
            $response['connection'] = 'refused';
            return $response;
        }catch (Exception $exception){
            Log::critical($exception->getMessage() . "\n" . $exception->getFile() . "\n" . $exception->getLine());
            $response['status'] = false;
            $response['response'] = $exception->getMessage();
            return $response;
        }

    }


}
