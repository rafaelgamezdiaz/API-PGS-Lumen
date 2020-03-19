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

    public function getClient($request, $id, $extended = true)
    {
        $endpoint = '/clients/'.$id;
        $client = $this->doRequest($request,'GET',  $endpoint)
            ->recursive()
            ->first();
        if ( $client == false) {
            return "Error! There is nor connection with API-Customers";
        }

        // Returns Client data. $extended == true --> full info, else returns specific fields.
        $client_fields = $client->only(['id','name','last_name','commerce_name']);
        return ($extended == true) ? $client : $client_fields;
    }
}
