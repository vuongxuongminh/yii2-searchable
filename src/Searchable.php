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

use TeamTNT\TNTSearch\TNTSearch;

/**
 * Class TNTSearch
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Searchable extends Component
{
    public $asYouType = false;

    public $mode = Searcher::FUZZY_MODE;

    public $fuzziness = false;

    public $fuzzyPrefixLength = 2;

    public $fuzzyMaxExpansions = 50;

    public $fuzzyDistance = 2;

    public $storagePath = '@runtime/vxm/searchable';

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->storagePath = Yii::getAlias($this->storagePath);

        parent::init();
    }

    /**
     * @param array $config
     * @param string|null $mode
     * @param Connection|null $db
     * @return Searcher
     * @throws \yii\base\InvalidConfigException
     */
    public function createSearcher(array $config = [], ?string $mode = null, ?Connection $db = null): Searcher
    {
        $db = $db ?? Yii::$app->getDb();
        $tnt = Yii::createObject([
            'class' => TNTSearch::class,
            'asYouType' => $config['asYouType'] ?? $this->asYouType,
            'fuzziness' => $config['fuzziness'] ?? $this->fuzziness,
            'fuzzy_distance' => $config['fuzzyDistance'] ?? $this->fuzzyDistance,
            'fuzzy_prefix_length' => $config['fuzzyPrefixLength'] ?? $this->fuzzyPrefixLength,
            'fuzzy_max_expansions' => $config['fuzzyMaxExpansions'] ?? $this->fuzzyMaxExpansions
        ]);
        $tnt->loadConfig(['storage' => $this->storagePath]);
        $tnt->setDatabaseHandle($db->getMasterPdo());

        return Yii::createObject(Searcher::class, [$tnt, $mode ?? $this->mode]);
    }

}
