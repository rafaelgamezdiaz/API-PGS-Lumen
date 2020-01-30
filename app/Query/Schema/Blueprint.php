<?php
/**
 * Created by PhpStorm.
 * User: zippyttech
 * Date: 03/08/18
 * Time: 04:43 PM
 */

namespace App\Query\Schema;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;

/**
 * Extended version of MySqlGrammar with
 * support of 'set' data type
 */
class ExtendedMySqlGrammar extends MySqlGrammar {

    /**
     * Create the column definition for an 'set' type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeSet(Fluent $column)
    {
        return "set('".implode("', '", $column->allowed)."')";
    }

}

/**
 * Extended version of Blueprint with
 * support of 'set' data type
 */
class ExtendedBlueprint extends Blueprint {

    /**
     * Create a new 'set' column on the table.
     *
     * @param  string  $column
     * @param  array   $allowed
     * @return \Illuminate\Support\Fluent
     */
    public function set($column, array $allowed)
    {
        return $this->addColumn('set', $column, compact('allowed'));
    }

}