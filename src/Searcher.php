<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use yii\base\BaseObject;
use yii\base\InvalidCallException;
use yii\queue\Queue;

/**
 * Class Searcher provide methods full-text search base on [teamtnt/tntsearch](https://github.com/teamtnt/tntsearch)
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Searcher extends BaseObject
{

    /**
     * Search data with boolean mode
     */
    const BOOLEAN_SEARCH = 'boolean';

    /**
     * Search data with fuzzy mode
     */
    const FUZZY_SEARCH = 'fuzzy';

    /**
     * @var TNTSearch
     */
    protected $tnt;

    /**
     * Searcher constructor.
     *
     * @param TNTSearch $tnt an object using to search index data
     * @param Queue $queue to dispatch searchable indexing data job to worker
     * @inheritDoc
     */
    public function __construct(TNTSearch $tnt, $config = [])
    {
        $this->tnt = $tnt;

        parent::__construct($config);
    }

    /**
     * Search by given query string.
     *
     * @param string $index apply to search.
     * @param string $query apply to search.
     * @param string $mode boolean or fuzzy.
     * @param int $limit of values search.
     * @return array search result.
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function search(string $index, string $query, string $mode, int $limit = 100): array
    {
        $this->tnt->selectIndex("{$index}.index");

        if ($mode === self::BOOLEAN_SEARCH) {
            return $this->tnt->searchBoolean($query, $limit);
        } else {
            return $this->tnt->search($query, $limit);
        }
    }

    /**
     * Update the given record in the index.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[] $models
     *
     * @return void
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function upsert($models)
    {
        foreach ((array)$models as $model) {
            if ($model->getIsNewRecord()) {
                throw new InvalidCallException('Can not upsert index data via new record!');
            }

            list($keyName, $keyValue) = $model->getSearchableKey(true);
            $this->tnt->selectIndex("{$model->searchableIndex()}.index");
            $index = $this->tnt->getIndex();
            $index->setPrimaryKey($keyName);

            $fields = $model->searchableFields();

            if (!empty($fields)) {
                $data = [];

                foreach ($fields as $field) {
                    if (strpos($field, ' ') !== false) {
                        list(, $alias) = explode(' ', $field, 2);
                    } else {
                        $alias = $field;
                    }

                    $data[$alias] = $model->getAttribute($field);
                }

                $index->indexBeginTransaction();
                $index->update($keyValue, $data);
                $index->indexEndTransaction();
            }
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[] $models
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws InvalidCallException
     */
    public function delete($models): void
    {
        foreach ((array)$models as $model) {
            if ($model->getIsNewRecord()) {
                throw new InvalidCallException('Can not delete index data via new record!');
            }

            list($keyName, $keyValue) = $model->getSearchableKey(true);
            $this->initIndex($model->searchableIndex(), $keyName);
            $this->tnt->selectIndex("{$model->searchableIndex()}.index");
            $index = $this->tnt->getIndex();
            $index->setPrimaryKey($keyName);
            $index->delete($keyValue);

        }
    }

    /**
     * Remove all of records of given record class from the engine.
     *
     * @param string $modelClass need to remove index data from the engine
     */
    public function deleteAll(string $modelClass): void
    {
        $indexName = $modelClass::searchableIndex();
        $pathToIndex = $this->tnt->config['storage'] . "/{$indexName}.index";

        if (file_exists($pathToIndex)) {
            unlink($pathToIndex);
        }
    }

    /**
     * @param string $index
     * @param string $primaryKey
     */
    protected function initIndex(string $index, string $primaryKey): void
    {
        if (!file_exists($this->tnt->config['storage'] . "/{$index}.index")) {
            $indexer = $this->tnt->createIndex("$index.index");
            $indexer->setPrimaryKey($primaryKey);
        }
    }

}
