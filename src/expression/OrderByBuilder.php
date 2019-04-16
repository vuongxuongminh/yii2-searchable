<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search\expression;

use yii\db\ExpressionBuilderInterface;
use yii\db\ExpressionBuilderTrait;
use yii\db\ExpressionInterface;

/**
 * Class OrderByBuilder for build the [[OrderBy]].
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class OrderByBuilder implements ExpressionBuilderInterface
{

    use ExpressionBuilderTrait;

    /**
     * @param ExpressionInterface|OrderBy $expression
     * @inheritDoc
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $orderBy = $expression->query->orderBy;

        if ($orderBy[0] === $expression && count($orderBy) === 1) {

            return $this->queryBuilder->buildExpression($expression->getExpression());
        } else { // user choice

            return '(SELECT NULL)';
        }
    }
}
