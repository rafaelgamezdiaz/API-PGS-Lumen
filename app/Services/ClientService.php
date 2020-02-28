<?php


namespace App\Services;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class ClientService extends BaseService
{
    use ApiResponser;

    public function __construct(Request $request)
    {
        $this->baseUri  = config('services.clients.base_url');
        $this->port     = config('services.clients.port');
        $this->secret   = config('services.clients.secret');
        $this->prefix   = config('services.clients.prefix');
        parent::__construct($request);
    }

    /**
     * Returns a Client from API-Customers, by id
     */
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

    /**
     * Returns a Client from API-Customers, by id
     */
    public function getClients($request, $account, $extended = true)
    {
        $endpoint = '/clients?account='.$account;
        //$endpoint = '/clients?account='.$account;
        $client = $this->doRequest($request,'GET',  $endpoint);
        if ( $client == false) {
            return "Error! There is nor connection with API-Customers";
        }

        // Returns Client data. $extended == true --> full info, else returns specific fields.
        $client_fields = $client['list']; //->only(['id', 'commerce_name']);
        return $client_fields;
        //return ($extended == true) ? $client : $client_fields;
    }
}
