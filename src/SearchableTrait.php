<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

use yii\db\Exception;

/**
 * Trait SearchableTrait support implementing full-text search for the active record classes.
 * Base ideas extract from [`laravel/scout`](https://github.com/laravel/scout) package.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
trait SearchableTrait
{

    /**
     * @return Searchable
     * @throws \yii\base\InvalidConfigException
     */
    public static function getSearchable(): Searchable
    {
        return Yii::$app->get('searchable');
    }

    /**
     * Creating active query had been apply search ids condition by given query string
     *
     * @param string $query to search data
     * @param string $mode using for query search, [[\vxm\search\Searcher::BOOLEAN_SEARCH]] or [[\vxm\search\Searcher::FUZZY_SEARCH]]
     * @param array $config of [[\vxm\search\TNTSearch]]
     * @return \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface query instance
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function search(string $query, string $mode = Searcher::FUZZY_SEARCH, array $config = [])
    {
        $ids = static::searchIds($query, $mode, $config);

        return static::createQueryBySearchedIds($ids);
    }

    /**
     * Creating active query had been apply geo search ids condition by given location and distance values.
     *
     * @param array $currentLocation longitude and latitude location. Ex: ['latitude' => 48.137154, 'longitude' => 11.576124]
     * @param int $distance of position need to find to current location
     * @param array $config of tnt search
     */
    public static function geoSearch(array $currentLocation, $distance, array $config = [])
    {

    }

    /**
     * Search ids by given query string.
     *
     * @param string $query to search data
     * @param string $mode using for query search, `\vxm\search\Searcher::BOOLEAN_MODE` or `\vxm\search\Searcher::FUZZY_MODE`
     * @param array $config of an object \vxm\search\Searcher
     * @return array primary key of indexing data search
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function searchIds(string $query, string $mode = Searcher::FUZZY_SEARCH, array $config = [])
    {
        $profileToken = "Searching data via query: `{$query}`";
        Yii::beginProfile($profileToken);
        /** @var Searcher $searcher */
        $searcher = static::getSearchable()->createSearcher(static::getDb(), $config);

        try {
            $result = $searcher->search($query, static::searchableIndex(), $mode);

            return $result['ids'];
        } finally {

            Yii::endProfile($profileToken);
        }
    }

    /**
     * Creating active query had been apply geo search ids condition by given location and distance values.
     *
     * @param array $currentLocation longitude and latitude location. Ex: ['latitude' => 48.137154, 'longitude' => 11.576124]
     * @param int $distance of position need to find to current location
     * @param array $config of tnt search
     */
    public static function geoSearchIds(array $currentLocation, int $distance, array $config = [])
    {
        $profileToken = "Searching position by location: lat: {$currentLocation['latitude']} - long: {$currentLocation['longitude']} distance: {$distance} km";
        Yii::beginProfile($profileToken);
        /** @var Searcher $searcher */
        $searcher = static::getSearchable()->createSearcher(static::getDb(), $config);

        try {
            $result = $searcher->search($query, static::searchableIndex(), $mode);

            return $result['ids'];
        } finally {

            Yii::endProfile($profileToken);
        }
    }

    /**
     * Create active query by searched ids
     *
     * @param array $ids use to add condition to query
     * @return \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface the query added given ids condition
     */
    private static function createQueryBySearchedIds(array $ids)
    {
        /** @var \yii\db\ActiveQuery $aq */
        $query = static::find();

        if (empty($ids)) {

            $query->andWhere('1 = 0');
        } else {
            /** @var \yii\db\Connection $db */
            $db = static::getDb();
            $db->setQueryBuilder([
                'expressionBuilders' => [
                    SearchableExpression::class => SearchableExpressionBuilder::class
                ]
            ]);
            $searchableExpression = new SearchableExpression([
                'query' => $aq,
                'ids' => $ids
            ]);
            $query->where = ['AND', $searchableExpression, $aq->where];
        }

        return $query;
    }

    /**
     * Delete all instances of the model from the search index.
     *
     */
    public static function deleteAllFromSearch(): void
    {
        static::getSearchable()->createSearcher(static::getDb())->deleteAll(static::class);
    }

    /**
     * Enable search syncing for this model.
     */
    public static function enableSearchSyncing(): void
    {
        SearchableBehavior::enableSyncingFor(static::class);
    }

    /**
     * Disable search syncing for this model.
     */
    public static function disableSearchSyncing(): void
    {
        SearchableBehavior::disableSyncingFor(static::class);
    }

    /**
     * Temporarily disable search syncing for the given callback.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function withoutSyncingToSearch($callback)
    {
        static::disableSearchSyncing();

        try {
            return $callback();
        } finally {
            static::enableSearchSyncing();
        }
    }

    /**
     * Make all instances of the model searchable.
     *
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function makeAllSearchable(): void
    {
        $models = static::find()->orderBy(static::searchableKey())->all();

        static::queueMakeSearchable($models);
    }

    /**
     * Get the index name for the model.
     *
     * @return string the name of an index
     */
    public static function searchableIndex(): string
    {
        return static::getDb()->quoteSql(static::tableName());
    }

    /**
     * Get the indexable data fields of the model. By default columns name of the table will be use
     *
     * @return array ['field' => 'value'] or ['field alias' => 'value']
     */
    public static function searchableFields(): array
    {
        return static::getTableSchema()->columns;
    }

    /**
     * Get searchable key by default primary key will be use.
     *
     * @return string|int key name
     */
    public static function searchableKey()
    {
        return current(static::primaryKey());
    }

    /**
     * Dispatch the job to make the given models searchable.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] array $models
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function queueMakeSearchable($models): void
    {
        $models = (array)$models;

        if (!empty($models)) {
            $searchable = static::getSearchable();

            if ($searchable->queue === null) {

                return $searchable->createSearcher(static::getDb())->upsert($models);
            }

            $job = new MakeSearchableJob($models);
            $searchable->queue->push($job);
        }
    }

    /**
     * Dispatch the job to make the given models unsearchable.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] array $models
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function queueDeleteFromSearch($models)
    {
        $models = (array)$models;

        if (!empty($models)) {
            $searchable = static::getSearchable();

            if ($searchable->queue === null) {

                return $searchable->createSearcher(static::getDb())->delete($models);
            }

            $job = new DeleteSearchableJob($models);
            $searchable->queue->push($job);
        }
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool weather instance should be insert to searchable index data
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * Make the given model instance searchable.
     *
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function searchable(): void
    {
        static::queueMakeSearchable($this);
    }

    /**
     * Remove the given model instance from the search index.
     *
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function unsearchable(): void
    {
        static::queueDeleteFromSearch($this);
    }

    /**
     * Get searchable key value by default the primary key will be use.
     *
     * @param bool $asArray weather return an array have a key is a searchable key and value is an value of key or only value.
     * @return string|int|string[]|int[] value of an searchable key
     * @throws Exception
     */
    public function getSearchableKey(bool $asArray = false)
    {
        $data = $this->attributes();
        $key = static::searchableKey();

        if ($asArray) {
            return [$key => $data[$key]];
        } else {
            return $data[$key];
        }
    }

}
