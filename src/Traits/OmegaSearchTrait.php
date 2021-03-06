<?php

namespace DivineOmega\LaravelOmegaSearch\Traits;

use DivineOmega\OmegaSearch\OmegaSearch;
use DivineOmega\OmegaSearch\SearchResults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait OmegaSearchTrait
{
    /**
     * Performs the search operation, and returns a query builder filtered
     * to the related records in descending order of relevance.
     *
     * @param string $searchText
     *
     * @param int $limit
     * @return Builder
     */
    public static function omegaSearch(string $searchText, int $limit = 100)
    {
        $searchResults = self::omegaSearchRaw($searchText, $limit);
        $query = self::buildQueryBuilderFromSearchResults($searchResults);

        return $query;
    }

    /**
     * Performs the search operation, and returns the raw results from the
     * Omega Search package, including record primary keys, relevance values,
     * and relevance statistics (highest, lowest, and average relevance).
     *
     * @param string $searchText
     * @param int $limit
     * @return SearchResults
     */
    public static function omegaSearchRaw(string $searchText, int $limit = 100)
    {
        /** @var Model $model */
        $model = new self();
        $keyName = $model->getKeyName();

        $search = (new OmegaSearch())
            ->setDatabaseConnection(DB::getPdo())
            ->setTable($model->getTable())
            ->setPrimaryKey($keyName)
            ->setFieldsToSearch($model->getOmegaSearchFieldsToSearch())
            ->setConditions($model->getOmegaSearchConditions());

        return $search->query($searchText, $limit);
    }

    /**
     * Builds and returns an Eloquent query builder object from the passed
     * Omega Search Search Results object, filtered to the related records
     * in descending order of relevance.
     *
     * @param SearchResults $searchResults
     * @return Builder
     */
    private static function buildQueryBuilderFromSearchResults(SearchResults $searchResults)
    {
        /** @var Model $model */
        $model = new self();
        $keyName = $model->getKeyName();

        $ids = array_map(function ($result) {
            return $result->id;
        }, $searchResults->results);

        /** @var Builder $query */
        $query = self::query();

        if (count($ids) > 0) {
            $query->whereIn($keyName, $ids)
                ->orderByRaw(DB::raw('FIELD(' . $keyName . ', ' . implode(',', $ids) . ')'));
        }

        return $query;
    }

    /**
     * Must return an array of the model's fields to search.
     *
     * @return array
     */
    abstract public function getOmegaSearchFieldsToSearch();

    /**
     * Must return an associative array of the search conditions.
     *
     * e.g ['active' => 1, 'discontinued' => 0]
     *
     * @return array
     */
    abstract public function getOmegaSearchConditions();
}
