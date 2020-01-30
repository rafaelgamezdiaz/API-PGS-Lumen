<?php


namespace App\Services;

use App\Traits\ApiResponser;

class UserService extends BaseService
{
    use ApiResponser;

    public function __construct()
    {
        $this->baseUri  = config('services.users.base_url');
        $this->port     = config('services.users.port');
        $this->secret   = config('services.users.secret');
        $this->prefix   = config('services.users.prefix');
    }

    /**
     * Returns a Client from API-Customers, by id
     */
    public function getUser($request, $username, $extended = true)
    {
        $endpoint = '/users/'.$username;

        $user = $this->doRequest($request, 'GET',  $endpoint)
                     ->recursive()
                     ->first();

        if ( $user == false) {
            return "Error! There is nor connection with API-Users";
        }

        // Returns Client data. $extended == true --> full info, else returns specific fields.
        $user_fields = $user->only(['id','name']);
        return ($extended == true) ? $user : $user_fields;
    }
}
