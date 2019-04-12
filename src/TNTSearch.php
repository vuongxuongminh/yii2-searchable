<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\tntsearch;

use Yii;

use yii\base\Component;
use yii\db\Connection;
use yii\di\Instance;

use TeamTNT\TNTSearch\TNTSearch as Searcher;


/**
 * Class TNTSearch
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class TNTSearch extends Component
{

    const FUZZY_MODE = 'fuzzy';

    const BOOLEAN_MODE = 'boolean';

    public $asYouType = false;

    public $mode = self::FUZZY_MODE;

    public $fuzziness = false;

    public $fuzzyPrefixLength = 2;

    public $fuzzyMaxExpansions = 50;

    public $fuzzyDistance = 2;

    public $indexPath = '@runtime/tntsearch';

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->indexPath = Yii::getAlias($this->indexPath);

        parent::init();
    }

    /**
     * @param string $query
     * @param string $index
     * @param int $limit
     * @param bool|null $asYouType
     * @param Connection|null $db
     * @return array
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function search(string $query, string $index, int $limit = 1000, ?bool $asYouType = null, ?Connection $db = null)
    {
        $searcher = $this->createSearcher($db);
        $searcher->selectIndex("{$index}.index");

        if ($asYouType !== null) {
            $searcher->asYouType = $asYouType;
        }

        if ($this->mode === self::BOOLEAN_MODE) {
            return $searcher->searchBoolean($query, $limit);
        } else {
            return $searcher->search($query, $limit);
        }
    }

    /**
     * @param string $index
     * @param string $primaryKey
     * @param Connection|null $db
     * @return Searcher
     */
    public function initIndex(string $index, string $primaryKey, ?Connection $db = null): Searcher
    {
        $searcher = $this->createSearcher($db);

        if (!file_exists($this->indexPath . "/{$index}.index")) {
            $indexer = $searcher->createIndex("$index.index");
            $indexer->setPrimaryKey($primaryKey);
        }

        return $searcher;
    }


    /**
     * @param Connection|null $db
     * @return Searcher
     */
    public function createSearcher(?Connection $db = null): Searcher
    {
        $db = $db ?? Yii::$app->getDb()->getMasterPdo();
        $searcher = new Searcher();
        $searcher->loadConfig(['storage' => $this->indexPath]);
        $searcher->setDatabaseHandle($db->getMasterPdo());
        $searcher->asYouType = $this->asYouType;
        $searcher->fuzziness = $this->fuzziness;
        $searcher->fuzzy_distance = $this->fuzzyDistance;
        $searcher->fuzzy_prefix_length = $this->fuzzyPrefixLength;
        $searcher->fuzzy_max_expansions = $this->fuzzyMaxExpansions;

        return $searcher;
    }


}
