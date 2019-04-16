<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-searchable
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\searchable\expression;

use yii\db\ExpressionBuilderInterface;
use yii\db\ExpressionBuilderTrait;
use yii\db\ExpressionInterface;

/**
 * Class ConditionBuilder for build the [[Condition]].
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class ConditionBuilder implements ExpressionBuilderInterface
{

    use ExpressionBuilderTrait;

    /**
     * @param ExpressionInterface|Condition $expression
     * @inheritDoc
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $condition = $expression->getExpression();

        return $this->queryBuilder->buildCondition($condition, $params);
    }
}
