<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

use yii\base\Component;
use yii\db\Connection;
use yii\di\Instance;
use yii\queue\Queue;

use TeamTNT\TNTSearch\TNTSearch;


/**
 * Class TNTSearch
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Searchable extends Component
{

    public $tntSearchClass = TNTSearch::class;

    public $asYouType = false;

    public $fuzziness = false;

    public $fuzzyPrefixLength = 2;

    public $fuzzyMaxExpansions = 50;

    public $fuzzyDistance = 2;

    public $storagePath = '@runtime/vxm/searchable';

    /**
     * @var Queue|null
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
