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

    public function getUsersInfo($request, $usersnames, $extended = true)
    {
        $fields = '"users.id","users.name"';
        $endpoint = '/users?where=[{"op":"in","field":"users.username","value":'.$usersnames.'}]&account='.$this->account.'&columns=['.$fields.']';
        //$users = $this->doRequest($request,'GET',  $endpoint);
        $users = $this->doRequest($request,'GET',  $endpoint)
            ->recursive()
            ->first();
        if ( $users == false) {
            return "Error! There is nor connection with API-Users";
        }
        $user_fields = $users->only(['id','name']);
        return ($extended == true) ? $users : $user_fields;
    }

    /**
     * Returns a Client from API-Customers, by id
     */
    public function getUser($request, $username, $extended = true)
    {
        $endpoint = '/users/'.$username;

        $user = collect($this->doRequest($request, 'GET',  $endpoint)->first())
                ->recursive();
        if ( $user == false) {
            return "Error! There is nor connection with API-Users";
        }
        // Returns Client data. $extended == true --> full info, else returns specific fields.
       return $user;
        $user_fields = $user->only(['id','name']);
        return ($extended == true) ? $user : $user_fields;
    }
}
