<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-searchable
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\searchable;

use Yii;

use yii\base\Component;
use yii\db\Connection;
use yii\di\Instance;
use yii\queue\Queue;

use vxm\searchable\queue\DeleteSearchable;
use vxm\searchable\queue\MakeSearchable;

/**
 * Class TNTSearch support full-text search via tnt search.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Searchable extends Component
{
    /**
     * Search data with boolean mode.
     */
    const BOOLEAN_SEARCH = 'boolean';

    /**
     * Search data with fuzzy mode.
     */
    const FUZZY_SEARCH = 'fuzzy';

    /**
     * @var string default search mode for [[search()]] if `$mode` param not set.
     */
    public $defaultSearchMode = self::FUZZY_SEARCH;

    /**
     * @var string the TNTSearch class.
     */
    public $tntSearchClass = TNTSearch::class;

    /**
     * @var bool default as you type search config.
     */
    public $asYouType = false;

    /**
     * @var bool default fuzziness search config.
     */
    public $fuzziness = false;

    /**
     * @var int default fuzzy prefix length config.
     */
    public $fuzzyPrefixLength = 2;

    /**
     * @var int default fuzzy max expansions config.
     */
    public $fuzzyMaxExpansions = 50;

    /**
     * @var int default fuzzy distance config.
     */
    public $fuzzyDistance = 2;

    /**
     * @var string default storage path of index data.
     */
    public $storagePath = '@runtime/vxm/searchable';

    /**
     * @var Queue|null use for support make or delete index data via worker.
     */
    public $queue;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->storagePath = Yii::getAlias($this->storagePath);

        if ($this->queue !== null) {
            $this->queue = Instance::ensure($this->queue, Queue::class);
        }

        parent::init();
    }

    /**
     * Search by model class via given query string.
     *
     * @param string $modelClass need to search.
     * @param string $query apply to search.
     * @param string $mode boolean or fuzzy search mode.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @param int $limit of values search.
     * @return array search results.
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function search(string $modelClass, string $query, ?string $mode = null, array $config = [], int $limit = 100): array
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $this->initIndex($modelClass, $config);
        $tnt = $this->createTNTSearch($modelClass::getDb(), $config);
        $tnt->selectIndex("{$modelClass::searchableIndex()}.index");
        $mode = $mode ?? $this->defaultSearchMode;

        if ($mode === self::BOOLEAN_SEARCH) {

            return $tnt->searchBoolean($query, $limit);
        } else {

            return $tnt->search($query, $limit);
        }
    }

    /**
     * Delete all instances of the model class from the search index.
     *
     * @param string $modelClass need to delete all instances.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @throws \yii\base\InvalidConfigException
     */
    public function deleteAllFromSearch(string $modelClass, array $config = []): void
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $tnt = $this->createTNTSearch($modelClass::getDb(), $config);
        $pathToIndex = $tnt->config['storage'] . "/{$modelClass::searchableIndex()}.index";

        if (file_exists($pathToIndex)) {
            unlink($pathToIndex);
        }
    }

    /**
     * Dispatch the job to make the given models unsearchable.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] $models dispatch to queue.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function queueDeleteFromSearch($models, array $config = []): void
    {
        $models = is_array($models) ? $models : [$models];

        if (empty($models)) {

            return;
        }

        if ($this->queue === null) {

            $this->delete($models, $config);
        } else {

            $job = new DeleteSearchable($models);
            $this->queue->push($job);
        }
    }

    /**
     * Dispatch the job to make the given models searchable.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] $models dispatch to queue job.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function queueMakeSearchable($models, array $config = []): void
    {
        $models = is_array($models) ? $models : [$models];

        if (empty($models)) {

            return;
        }

        if ($this->queue === null) {

            $this->upsert($models, $config);
        } else {

            $job = new MakeSearchable($models);
            $this->queue->push($job);
        }
    }

    /**
     * Update or insert models to search engine.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] $models dispatch to queue.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function upsert($models, array $config = []): void
    {
        $models = is_array($models) ? $models : [$models];
        /** @var \yii\db\ActiveRecord $modelClass */
        try {
            $modelClass = get_class(current($models));
        } catch (\Throwable $t) {
            var_dump(current($models));
            die;
        }
        $this->initIndex($modelClass, $config);
        $tnt = $this->createTNTSearch($modelClass::getDb(), $config);
        $tnt->selectIndex("{$modelClass::searchableIndex()}.index");
        $index = $tnt->getIndex();
        $index->setPrimaryKey($modelClass::searchableKey());

        foreach ($models as $model) {
            /** @var \yii\db\ActiveRecord $model */

            $data = $model->toSearchableArray();

            if (empty($data)) {
                return;
            }

            $index->indexBeginTransaction();
            $index->update($model->getSearchableKey(), $data);
            $index->indexEndTransaction();
        }
    }

    /**
     * Delete models from search engine.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] $models need to delete.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function delete($models, array $config = []): void
    {
        $models = is_array($models) ? $models : [$models];
        /** @var \yii\db\ActiveRecord $modelClass */
        $modelClass = get_class(current($models));
        $this->initIndex($modelClass, $config);
        $tnt = $this->createTNTSearch($modelClass::getDb(), $config);
        $tnt->selectIndex("{$modelClass::searchableIndex()}.index");
        $index = $tnt->getIndex();
        $index->setPrimaryKey($modelClass::searchableKey());

        foreach ($models as $model) {
            /** @var \yii\db\ActiveRecord $model */
            $index->delete($model->getSearchableKey());
        }
    }

    /**
     * Init index data of model class.
     *
     * @param string $modelClass to init index data.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @throws \yii\base\InvalidConfigException
     */
    public function initIndex(string $modelClass, array $config = []): void
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $index = $modelClass::searchableIndex() . '.index';
        $tnt = $this->createTNTSearch($modelClass::getDb(), $config);

        if (!file_exists($tnt->config['storage'] . "/{$index}")) {
            $indexer = $tnt->createIndex($index);
            $indexer->setPrimaryKey($modelClass::searchableKey());
        }
    }

    /**
     * Create tnt search object.
     *
     * @param Connection|null $db use to get database info.
     * @param array $config of [[\vxm\searchable\TNTSearch]].
     * @return object|TNTSearch
     * @throws \yii\base\InvalidConfigException
     */
    public function createTNTSearch(?Connection $db = null, array $config = []): TNTSearch
    {
        $db = $db ?? Yii::$app->getDb();
        $dbh = $db->getMasterPdo();
        $tnt = Yii::createObject([
            'class' => $this->tntSearchClass,
            'asYouType' => $config['asYouType'] ?? $this->asYouType,
            'fuzziness' => $config['fuzziness'] ?? $this->fuzziness,
            'fuzzy_distance' => $config['fuzzy_distance'] ?? $config['fuzzyDistance'] ?? $this->fuzzyDistance,
            'fuzzy_prefix_length' => $config['fuzzy_prefix_length'] ?? $config['fuzzyPrefixLength'] ?? $this->fuzzyPrefixLength,
            'fuzzy_max_expansions' => $config['fuzzy_max_expansions'] ?? $config['fuzzyMaxExpansions'] ?? $this->fuzzyMaxExpansions
        ]);
        $tnt->loadConfig(['storage' => $this->storagePath]);
        $tnt->setDatabaseHandle($dbh);

        return $tnt;
    }


}
