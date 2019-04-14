<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

use yii\base\BaseObject;
use yii\db\ActiveRecordInterface;
use yii\db\Exception;

/**
 * Class Searcher
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Searcher extends BaseObject
{

    const FUZZY_MODE = 'fuzzy';

    const BOOLEAN_MODE = 'boolean';

    /**
     * @var null|string
     */
    protected $mode;

    /**
     * @var TNTSearch
     */
    protected $tnt;

    /**
     * Searcher constructor.
     * @param TNTSearch $tnt
     * @param string $mode
     * @param array $config
     */
    public function __construct(TNTSearch $tnt, string $mode, $config = [])
    {
        $this->tnt = $tnt;
        $this->mode = $mode;

        parent::__construct($config);
    }


    /**
     * @param string $query
     * @param string $index
     * @param int $limit
     * @param bool|null $asYouType
     * @return array
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function search(string $query, string $index, int $limit = 1000)
    {
        $this->tnt->selectIndex("{$index}.index");

        if ($this->mode === self::BOOLEAN_MODE) {
            return $this->tnt->searchBoolean($query, $limit);
        } else {
            return $this->tnt->search($query, $limit);
        }
    }

    /**
     * Update the given record in the index.
     *
     * @param ActiveRecordInterface $record
     *
     * @return void
     * @throws Exception
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function update(ActiveRecordInterface $record)
    {
        $keyName = $this->getKeyName($record);
        $this->tnt->selectIndex("{$record->searchableIndex()}.index");
        $index = $this->tnt->getIndex();
        $index->setPrimaryKey($keyName);

        $fields = $record->searchableFields();

        if (!empty($fields)) {
            $data = [];

            foreach ($fields as $field) {
                $data[$field] = $record->getAttribute($field);
            }

            $index->indexBeginTransaction();

            if ($key = $this->getKey($record)) {
                $index->update($key, $data);
            } else {
                $index->insert($data);
            }

            $index->indexEndTransaction();
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param ActiveRecordInterface $record
     * @throws Exception
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function delete(ActiveRecordInterface $record): void
    {
        $keyName = $this->getKeyName($record);
        $this->initIndex($record->searchableIndex(), $keyName);
        $this->tnt->selectIndex("{$record->searchableIndex()}.index");
        $index = $this->tnt->getIndex();
        $index->setPrimaryKey($keyName);
        $index->delete($this->getKey($record));
    }

    /**
     * Remove all of records of given record class from the engine.
     *
     * @param string $recordClass
     */
    public function deleteAll(string $recordClass): void
    {
        $indexName = $recordClass::searchableIndex();
        $pathToIndex = $this->tnt->config['storage'] . "/{$indexName}.index";

        if (file_exists($pathToIndex)) {
            unlink($pathToIndex);
        }
    }

    /**
     * @param ActiveRecordInterface $record
     * @return string
     * @throws Exception
     */
    protected function getKeyName(ActiveRecordInterface $record): string
    {
        $keys = $record->primaryKey();

        if (empty($keys)) {

            throw new Exception(get_class($this) . ' does not have a primary key. You should either define a primary key for the corresponding table or override the primaryKey() method.');
        } else {
            return $keys[0];
        }
    }

    /**
     * @param ActiveRecordInterface $record
     * @return mixed
     * @throws Exception
     */
    protected function getKey(ActiveRecordInterface $record): string
    {
        $key = $this->getKeyName($record);
        $keyValues = $record->getPrimaryKey(true);

        return $keyValues[$key];
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
