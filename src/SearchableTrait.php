<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

use yii\db\ActiveQueryInterface;
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
     * @inheritDoc
     * @return \yii\db\Connection
     */
    abstract public static function getDb();

    /**
     * @inheritDoc
     * @return string
     */
    abstract public static function tableName();

    /**
     * @inheritDoc
     * @return \yii\db\TableSchema
     */
    abstract public static function getTableSchema();

    /**
     * @inheritDoc
     * @return mixed
     */
    abstract public static function primaryKey();

    /**
     * @inheritDoc
     * @return \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface
     */
    abstract public static function find();

    /**
     * Get searchable support full-text search for this model class.
     *
     * @return object|Searchable
     * @throws \yii\base\InvalidConfigException
     */
    public static function getSearchable(): Searchable
    {
        return Yii::$app->get('searchable');
    }

    /**
     * Creating active query had been apply search ids condition by given query string.
     *
     * @param string $query to search data.
     * @param string $mode using for query search, [[\vxm\search\Searchable::BOOLEAN_SEARCH]] or [[\vxm\search\Searchable::FUZZY_SEARCH]].
     * If not set [[\vxm\search\Searchable::$defaultSearchMode]] will be use.
     * @param array $config of [[\vxm\search\TNTSearch]].
     * @return \yii\db\ActiveQuery|ActiveQueryInterface query instance.
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function search(string $query, ?string $mode = null, array $config = []): ActiveQueryInterface
    {
        $ids = static::searchIds($query, $mode, $config);
        /** @var \yii\db\ActiveQuery $aq */
        $aq = static::find();

        if (empty($ids)) {

            $aq->andWhere('1 = 0');
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
            $aq->andWhere($searchableExpression);
        }

        return $aq;
    }

    /**
     * Search ids by given query string.
     *
     * @param string $query to search data.
     * @param string|null $mode using for query search, [[\vxm\search\Searchable::BOOLEAN_SEARCH]] or [[\vxm\search\Searchable::FUZZY_SEARCH]].
     * If not set [[\vxm\search\Searchable::$defaultSearchMode]] will be use.
     * @param array $config of [[\vxm\search\TNTSearch]].
     * @return array search key values of indexing data search.
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function searchIds(string $query, ?string $mode = null, array $config = []): array
    {
        $profileToken = "Searching data via query: `{$query}`";
        Yii::beginProfile($profileToken);

        try {
            $result = static::getSearchable()->search(static::class, $query, $mode, $config);

            return $result['ids'];
        } finally {

            Yii::endProfile($profileToken);
        }
    }

    /**
     * Delete all instances of the model from the search index.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public static function deleteAllFromSearch(): void
    {
        static::getSearchable()->deleteAllFromSearch(static::class);
    }

    /**
     * Enable search syncing for this model class.
     */
    public static function enableSearchSyncing(): void
    {
        SearchableBehavior::enableSyncingFor(static::class);
    }

    /**
     * Disable search syncing for this model class.
     */
    public static function disableSearchSyncing(): void
    {
        SearchableBehavior::disableSyncingFor(static::class);
    }

    /**
     * Temporarily disable search syncing for the given callback.
     *
     * @param callable $callback will be call without syncing mode.
     * @return mixed value of $callback.
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
        foreach (static::find()->orderBy(static::searchableKey())->batch() as $models) {
            $models = array_filter($models, function ($model) {
                return $model->shouldBeSearchable();
            });

            static::getSearchable()->queueMakeSearchable($models);
        }
    }

    /**
     * Get the index name for the model.
     *
     * @return string the name of an index.
     */
    public static function searchableIndex(): string
    {
        return static::getDb()->quoteSql(static::tableName());
    }

    /**
     * Get the indexable data fields of the model. By default columns name of the table will be use.
     *
     * @return array ['field' => 'value'] or ['field alias' => 'value'].
     * @throws \yii\base\InvalidConfigException
     */
    public static function searchableFields(): array
    {
        return array_keys(static::getTableSchema()->columns);
    }

    /**
     * Get searchable key by default primary key will be use.
     *
     * @return string|int key name.
     */
    public static function searchableKey()
    {
        return current(static::primaryKey());
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool weather instance should be insert to searchable index data.
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
        static::getSearchable()->queueMakeSearchable($this);
    }

    /**
     * Remove the given model instance from the search index.
     *
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function unsearchable(): void
    {
        static::getSearchable()->queueDeleteFromSearch($this);
    }

    /**
     * Get searchable key value by default the primary key will be use.
     *
     * @param bool $asArray weather return an array have a key is a searchable key and value is an value of key or only value.
     * @return string|int|string[]|int[] value of an searchable key.
     * @throws Exception
     */
    public function getSearchableKey(bool $asArray = false)
    {
        $key = static::searchableKey();

        if ($asArray) {
            return [$key => $this->$key];
        } else {
            return $this->$key;
        }
    }

}
