<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search\expression;

use yii\db\Expression as DbExpression;
use yii\db\ExpressionInterface;

/**
 * Class OrderBy support add order by search result for make result have been order by exact ids.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class OrderBy extends Expression
{

    /**
     * @inheritDoc
     * @return ExpressionInterface|
     */
    public function getExpression(): ExpressionInterface
    {
        $position = 1;
        $cases = ['CASE'];
        $params = [];
        $searchableKey = $this->searchableKey();

        foreach ($this->ids as $id) {
            $paramName = ":sob{$position}";
            $cases[] = "WHEN {$searchableKey} = {$paramName} THEN {$position}";
            $params[$paramName] = $id;
            $position++;
        }

        $cases[] = 'ELSE ' . $position;
        $cases[] = 'END ASC';

        return new DbExpression(implode(' ', $cases), $params);
    }


}
