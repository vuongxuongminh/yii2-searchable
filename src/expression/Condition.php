<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search\expression;

use yii\db\conditions\InCondition;
use yii\db\ExpressionInterface;

/**
 * Class Condition provide searchable condition have been ensured table alias.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Condition extends Expression
{

    /**
     * @inheritDoc
     */
    public function getExpression(): ExpressionInterface
    {
        return new InCondition($this->searchableKey(), 'IN', $this->ids);
    }

}
