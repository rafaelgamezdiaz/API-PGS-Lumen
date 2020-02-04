<?php

namespace App\Query;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Query\Exceptions\InvalidSortQuery;
use App\Query\Exceptions\InvalidFilterQuery;
use App\Query\Exceptions\InvalidIncludeQuery;
use App\Traits\doWhereTrait;

class QueryBuilder extends Builder
{
    use doWhereTrait;
    /** @var \Illuminate\Http\Request */
    protected $request;

    public function __construct(Builder $builder, ? Request $request = null)
    {
        parent::__construct(clone $builder->getQuery());

        $this->initializeFromBuilder($builder);

        $this->request = $request ;
    }

  
    protected function initializeFromBuilder(Builder $builder)
    {
        $this->setModel($builder->getModel())
            ->setEagerLoads($builder->getEagerLoads());

        $builder->macro('getProtected', function (Builder $builder, string $property) {
            return $builder->{$property};
        });

        $this->scopes = $builder->getProtected('scopes');

        $this->localMacros = $builder->getProtected('localMacros');

        $this->onDelete = $builder->getProtected('onDelete');
    }

    public static function for($baseQuery, ? Request $request = null) : self
    {
        if (is_string($baseQuery)) {
            $baseQuery = ($baseQuery)::query();
        }

        return new static($baseQuery, $request );
    }

}
