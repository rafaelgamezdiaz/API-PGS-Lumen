<?php


namespace App\Models;


use App\Query\QueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $modelo;
    protected $fillable = ['*'];


    /**
     * @named Funcion para ejecutar el doWhere en el Modelo para ello recibe dos parametros el query y Request
     */
    public function scopeDoWhere($query, $request) {


        $list = QueryBuilder::for(static::class)
            ->select($this->getColumns($request))
            ->doJoin($this->getJoins($request))
            ->doWhere($this->getWhere($request))
            ->sort($this->getSort($request));

        if(isset($_GET['limit']))
        {
            $list->limit($_GET['limit']);
        }

        if(isset($_GET['count']))
        {
            $list->selectRaw("count({$_GET['count']}) as total");
        }
        if(isset($_GET['group']))
        {
            $list->groupBy($this->getGroups($request));
        }
        return $list;
    }

    /**
     * @named Funcion para paginar los registros de la consulta
     * @param $request
     * @return mixed
     */
    public function limit($request)
    {
        if(isset($_GET['paginate']))
        {
            $paginate = $request->paginate;
        }
        return $paginate;
    }

    /**
     * @named Funcion para obtener el paginado de la consulta de 10 en 10
     * @param $request
     * @return int
     */
    public function getPag($request)
    {
        $paginate = 10;
        if(isset($_GET['paginate']))
        {
            $paginate = $request->paginate;
        }
        return $paginate;
    }

    /**
     * @named Funcion para obtener el Join
     * @param $request
     * @return |null
     */
    public function getJoins($request)
    {
        $joins = null;
        if(isset($_GET['join']))
        {
            $joins = $request->join;
        }
        return $joins;
    }

    /**
     * @named Funcion para Obtener el Order
     * @param $request
     * @return |null
     */
    public function getSort($request)
    {
        $sort = null;
        if(isset($_GET['sort']))
        {
            $sort = $request->sort;
        }
        return $sort;
    }

    /**
     * @named Funcion para obtener las columnas
     * @param $request
     * @return array|mixed
     */
    public function getColumns($request)
    {
        $columns = ['*'];
        if(isset($_GET['columns']))
        {
            $c = json_decode($request->columns);
            $columns = $c;
        }

        return $columns;
    }

    /**
     * @named Funcion para obtener el grupo o agrupado
     * @param $request
     * @return array|mixed
     */
    public function getGroups($request)
    {
        $groups = ['*'];
        if(isset($_GET['group']))
        {
            $c = json_decode($request->group);
            $groups = $c;
        }

        return $groups;
    }

    /**
     * @named Funcion para obtener el where
     * @param $request
     * @return |null
     */
    public function getWhere($request)
    {
        $where = null;
        if(isset($_GET['where']))
        {

            $where = $request->where;
        }

        return $where;
    }

    /**
     * @named Funcion para el el Modelo
     * @return string
     */
    public function getModel()
    {

        return get_class($this);

    }

    /**
     * @named Funcion para obtener el Nombre de la clase
     * @return string
     */
    public function getNameOfClass()
    {
        return static::class;
    }

    /**
     * @param $value
     * @return string
     */
    public function parseDate(string $value){
        $request = app('Illuminate\Http\Request');
        $timezone = ($request->hasHeader('x-timezone')) ? explode(',', $request->header('x-timezone'))[0]  : "America/Panama";
        return Carbon::parse($value,'UTC')->setTimezone($timezone)->toDateTimeString();
    }

}
