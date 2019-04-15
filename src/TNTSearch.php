<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

use yii\base\Configurable;

use TeamTNT\TNTSearch\TNTGeoSearch as BaseTNTSearch;

/**
 * Class TNTSearch
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class TNTSearch extends BaseTNTSearch implements Configurable
{
    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }

        parent::__construct();
    }

}
