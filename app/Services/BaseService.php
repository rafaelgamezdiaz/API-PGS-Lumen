<?php


namespace App\Services;


use App\Traits\ConsumesExternalService;
use Illuminate\Http\Request;

class BaseService
{
    use ConsumesExternalService;

    protected $account;
    protected $username;

    public function __construct(Request $request)
    {
        $this->account = $this->getAccount($request);
        $this->username = $this->getUserName($request);
    }

    public function doRequest($request, $method, $endpoint, $params = null)
    {
        $url = $this->getURL().$endpoint;
        $headers = $this->getHeaders($request);
        $response = $this->performRequest($method,$url,$params,$headers);
        return collect($response);
    }

    public function getResource($request, $endpoint)
    {
        $resource = $this->doRequest($request,'GET',  $endpoint);
        if ( $resource['status'] == false  ) {
            if (is_object($resource['response'])) {
                return ['status' => $resource['response']->status, 'error' => $resource['response']->message ];
            }else{
                return ['status' => $resource['response']['status'], 'error' => $resource['response']['message']];
            }
        }
        return [
            'status' => 200,
            'list' =>  $resource['list']
        ];
    }

}
