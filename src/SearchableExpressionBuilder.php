<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use yii\db\ExpressionBuilderInterface;
use yii\db\ExpressionBuilderTrait;
use yii\db\ExpressionInterface;

/**
 * Class SearchableConditionBuilder for build the [[SearchableCondition]]
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class SearchableExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * @param ExpressionInterface|SearchableExpression $expression
     * @inheritDoc
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $condition = $expression->getCondition();

        return $this->queryBuilder->buildCondition($condition, $params);
    }
}
