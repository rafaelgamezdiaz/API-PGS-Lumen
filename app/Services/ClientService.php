<?php


namespace App\Services;

use App\Traits\ApiResponser;

class ClientService extends BaseService
{
    use ApiResponser;

    public function __construct()
    {
        $this->baseUri  = config('services.clients.base_url');
        $this->port     = config('services.clients.port');
        $this->secret   = config('services.clients.secret');
        $this->prefix   = config('services.clients.prefix');
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
}
