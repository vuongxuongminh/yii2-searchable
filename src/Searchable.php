<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

use yii\base\Component;
use yii\db\Connection;
use yii\di\Instance;
use yii\queue\Queue;

/**
 * Class TNTSearch support full-text search via tnt search
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Searchable extends Component
{
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
     * @param string $modelClass need to search
     * @param string $query apply to search.
     * @param string $mode boolean or fuzzy search mode.
     * @param array $config of tnt search.
     * @return array search results.
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function search(string $modelClass, string $query, string $mode, array $config = [])
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $db = $modelClass::getDb();
        $index = $modelClass::searchableIndex();

        return $this->createSearcher($db, $config)->search($index, $query, $mode);
    }

    /**
     * Delete all instances of the model class from the search index.
     *
     * @param string $modelClass need to delete all instances.
     * @throws \yii\base\InvalidConfigException
     */
    public function deleteAllFromSearch(string $modelClass): void
    {
        /** @var \yii\db\ActiveRecord $modelClass */

        $this->createSearcher($modelClass::getDb())->deleteAll($modelClass);
    }

    /**
     * Dispatch the job to make the given models searchable.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] array $models
     * @param array $config of tnt search
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function queueMakeSearchable($models, array $config = []): void
    {
        $models = (array)$models;

        if (!empty($models)) {
            /** @var \yii\db\ActiveRecord $modelClass */
            $modelClass = get_class(current($models));

            if ($this->queue === null) {

                $this->createSearcher($modelClass::getDb(), $config)->upsert($models);
            } else {

                $job = new MakeSearchableJob($models);
                $this->queue->push($job);
            }
        }
    }

    /**
     * Dispatch the job to make the given models unsearchable.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[]|static|static[] array $models
     * @param array $config of tnt search
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function queueDeleteFromSearch($models, array $config = []): void
    {
        $models = (array)$models;

        if (!empty($models)) {
            /** @var \yii\db\ActiveRecord $modelClass */
            $modelClass = get_class(current($models));

            if ($this->queue === null) {

                $this->createSearcher($modelClass::getDb(), $config)->delete($models);
            } else {

                $job = new DeleteSearchableJob($models);
                $this->queue->push($job);
            }
        }
    }

    /**
     * @param Connection|null $db
     * @param array $config
     * @return Searcher
     * @throws \yii\base\InvalidConfigException
     */
    public function createSearcher(?Connection $db = null, array $config = []): Searcher
    {
        $db = $db ?? Yii::$app->getDb();
        $dbh = $db->getMasterPdo();
        $tnt = Yii::createObject([
            'class' => $this->tntSearchClass,
            'asYouType' => $config['asYouType'] ?? $this->asYouType,
            'fuzziness' => $config['fuzziness'] ?? $this->fuzziness,
            'fuzzy_distance' => $config['fuzzyDistance'] ?? $this->fuzzyDistance,
            'fuzzy_prefix_length' => $config['fuzzyPrefixLength'] ?? $this->fuzzyPrefixLength,
            'fuzzy_max_expansions' => $config['fuzzyMaxExpansions'] ?? $this->fuzzyMaxExpansions
        ]);
        $tnt->loadConfig(['storage' => $this->storagePath]);
        $tnt->setDatabaseHandle($dbh);

        return Yii::createObject(Searcher::class, [$tnt]);
    }


}
