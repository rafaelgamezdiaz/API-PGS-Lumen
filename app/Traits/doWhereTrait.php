<?php
namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait doWhereTrait
{

    public function sort($sort)
    {
// example order=["id","desc"]
        if ($sort) {
            $sort = json_decode($sort);
            $this->orderBy($sort[0], $sort[1]);
        }
        return $this;
    }


    public function doWhere($where)
    {
        if (isset($where)) {
            $where = json_decode($where);
            if(is_array($where)){
                for ($i=0; $i < count($where); $i++) {
                    $array = $this->evalWhere($where[$i]);
                    $field =  $array[0];
                    $operator = $array[1];
                    $value = $array[2];
                    $filter = $array[3];
                    switch ($where[$i]->op) {
                        case 'bt':
                            $this->whereBetween($field,$value);
                            break;
                        case 'gt':
                            $this->where($field,$operator,$value);
                            break;
                        case 'gte':
                            $this->where($field,$operator,$value);
                            break;
                        case 'in':
                            $this->whereIn($field,$value,$filter);
                            break;
                        case 'ct':
                            if ($filter == 'or'){
                                $this->orWhereRaw("lower({$field}) like lower('%{$value}%')");

                            }else{
                                $this->whereRaw("lower({$field}) like lower('%{$value}%')");

                            }
                            break;
                        case 'sw':
                            if ($filter == 'or') {
                                $this->orWhereRaw("lower({$field}) like lower('{$value}%')");
                            }else{
                                $this->whereRaw("lower({$field}) like lower('{$value}%')");

                            }
                            break;
                        case 'ew':
                            if ($filter == 'or') {
                                $this->orWhereRaw("lower({$field}) like lower('%{$value}')");

                            }else{
                                $this->whereRaw("lower({$field}) like lower('%{$value}')");
                            }
                            break;
                        case 'nn':
                            $this->whereNotNull($field);
                            break;
                        case 'dt':
                            $this->where($field,"<>",$value);
                            break;
                        case 'eq':
                            if (isset($where[$i]->type) && $where[$i]->type == "date") {
                                $this->whereDate($field,$operator,$value,$filter);
                                //$this->whereRaw("lower({$field}) like lower('%{$value}')");
                            }else {
                                $this->where($field, $operator, $value, $filter);
                            }
                    }

                }
            }
        }

        return $this;
    }

    public function forWhere($where){
        $where = json_decode($where);
        if(is_array($where)){
            for ($i=0; $i < count($where); $i++) {
                print_r($where[$i]);
                return $where[$i];
            }
        }
    }

    public function evalWhere($where)
    {
        if(isset($where->op) && isset($where->field) && isset($where->value)) {
            $op = $this->getOp($where->op);
            if (!isset($where->filter)) {
                $where->filter = 'and';
            }

            $array = [$where->field, $op, $where->value, $where->filter];
            //print_r($array);
            return $array;
        }
    }

    public function evalJoin($join)
    {
        if(isset($join->table) && isset($join->fk) && isset($join->pk)) {

            if (!isset($join->type)) {
                $join->filter = 'inner';
            }

            $array = [$join->table, $join->fk, $join->pk, $join->type];

            return $array;
        }
    }

    public function evalMeWhere($string)
    {
        $array = [];
        $a = str_replace('"', "", $string);
        $b = str_replace('[', '', $a);
        $d = str_replace(']', '', $b);

        $val = [];
        $arrayWhere = explode(',', $d);

        for ($i=0; $i < count($arrayWhere) ; $i++) {
            array_push($val,(explode(':', $arrayWhere[$i])));
        }
        $posicion=[];
        for ($i=0; $i < 3; $i++) {
            $ope = $val[$i];
            for ($j=0; $j < 2; $j++) {
                array_push($posicion, $ope[$j]);

            }
        }


        return $arraglo = [
            $posicion[0] => $posicion[1],
            $posicion[2] => $posicion[3],
            $posicion[4] => $posicion[5]
        ];


    }

    public function getOp($op)
    {
        switch ($op) {
            case 'ct'://contiene algo en un string
                return 'like';
                break;
            case 'sw': //k% comienza con
                return 'like';
                break;
            case 'ew':   //%k temina con
                return 'like';
                break;
            case 'eq':  // = igual a
                return '=';
                break;
            case 'gt':  //mayor que
                return '>';
                break;
            case 'gte':  //mayor o igual
                return '>=';
                break;
            case 'lte':  //menor o igual
                return '<=';
                break;
            case 'lt':   // menor que
                return '<';
                break;
            case 'dt':   // distinto
                return '<>';
                break;
            case 'in':  // en []
                return 'in';
                break;
            case 'bt': //entre
                return 'bt';
                break;
            case 'nn': //distinto
                return 'nn';
                break;
            default:
                return '=';
                break;
        }
    }
    public function errorException(\Exception $e)
    {
        Log::critical("Error, archivo del peo: {$e->getFile()}, linea del peo: {$e->getLine()}, el peo: {$e->getMessage()}");
        return response()->json([
            "message" => "Error de servidor",
            "exception" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "code" => $e->getCode()
        ], 500);
    }

    public static function findTatuco($column, $dato, $status=null, $columns = ['*'])
    {
        $user = \JWTAuth::parseToken()->authenticate();
        $account = 'acc_id';
        return (new static)->newQuery()
            ->where($status,'=',true)
            ->where($account,'=',$user->acc_id)
            ->where($column, $dato)
            ->get();
    }

    public function doJoin($join)
    {
        if (isset($join)) {
            $join = json_decode($join);
            if(is_array($join)){//->leftJoin('table2 AS b', 'a.field2', '=', 'b.field2')
                for ($i=0; $i < count($join); $i++) {
                    $array = $this->evalJoin($join[$i]);
                    $table2 =  $array[0];
                    $foreign = $array[1];
                    $primary = $array[2];
                    $type = $array[3];

                    $this->join($table2,$foreign,'=',$primary,$type);
                }
            }
        }

        return $this;
    }
//&join=[{"table":"targets","fk":"spaces.target_id","pk":"targets.id","type":"inner"}]
    /**where=[{"op":"bt","field":"o.id","value":[1,3],"filter":"and"},{"op":"in","field":"o.id","value":[5,9],"filter":"or"},{"op":"eq","field":"o.id","value":4,"filter":"or"}]*/
}
