<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\tntsearch;

use yii\db\ActiveQueryInterface;

/**
 * Trait SearchableTrait
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
trait SearchableTrait
{

    /**
     * Creates an [[ActiveQueryInterface]] instance for query purpose.
     *
     * @return ActiveQueryInterface the newly created [[ActiveQueryInterface]] instance.
     */
    abstract public static function find();

    /**
     *
     * @param string $query
     */
    public static function search($query)
    {

    }

}
