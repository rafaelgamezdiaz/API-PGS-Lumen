<?php


namespace App\Services;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class UserService extends BaseService
{
    use ApiResponser;

    public function __construct(Request $request)
    {
        $this->baseUri  = config('services.users.base_url');
        $this->port     = config('services.users.port');
        $this->secret   = config('services.users.secret');
        $this->prefix   = config('services.users.prefix');
        parent::__construct($request);
    }

    /**
     * Returns all Users from API-Users from lot of arrays of usernames
     * usernames = [ [], [], [] ]
     */
    public function getUsersList($request, $usersnames)
    {
        $users_list = array();
        foreach ($usersnames as $usersnames_lot) {
            $fields = '"users.id","users.name"';
            $endpoint = '/users?where=[{"op":"in","field":"users.username","value":' . collect($usersnames_lot) . '}]&account=' . $this->account . '&columns=[' . $fields . ']';
            $users = $this->getResource($request, $endpoint);
            if ($users['status'] !== 200 ) {
                return $users;
            }
            foreach ($users['list'] as $user) {
                $users_list[$user['username']] = collect($user)->only(['id','name']);
            }
        }
        return [
            'status' => 200,
            'list' => $users_list
        ];
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
        $user_fields = $user->only(['id','name']);
        return ($extended == true) ? $user : $user_fields;
    }
}
