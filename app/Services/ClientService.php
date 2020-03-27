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
     * Returns all Client from API-Customers from lot of arrays of ids
     * id = [ [], [], [] ]
     */
    public function getClientsList($request, $ids)
    {
        $clients_list = array();
        foreach ($ids as $id){
            $fields = '"clients.id","clients.name","clients.last_name","clients.commerce_name","clients.contract"';
            $endpoint = '/clients?where=[{"op":"in","field":"clients.id","value":'.collect($id).'}]&account='.$this->account.'&columns=['.$fields.']';
            $clients = $this->getResource($request, $endpoint);
            if ($clients['status'] !== 200 ) {
                return $clients;
            }
            foreach ( $clients['list'] as $client ){
                $clients_list[$client['id']] = $client;
            }
        }

        return [
            'status' => 200,
            'list' => $clients_list
        ];
    }

    /**
     * Returns a Client from API-Customers, by id
     * @param $request
     * @param $id
     * @param bool $extended
     * @return string
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
        $client_fields = $client->only(['id','name','last_name','commerce_name', 'contract']);
        return ($extended == true) ? $client : $client_fields;
    }

}
