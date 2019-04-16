<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search\expression;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\conditions\InCondition;
use yii\db\ExpressionInterface;

/**
 * Class Expression make a searchable expression.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
abstract class Expression extends BaseObject implements ExpressionInterface
{
    /**
     * @var \yii\db\ActiveQuery
     */
    public $query;

    /**
     * @var int[]|string[]
     */
    public $ids = [];

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->ids)) {
            throw new InvalidConfigException('`ids` property must be set to detect id instance!');
        }

        if ($this->query === null) {
            throw new InvalidConfigException('`query` property must be set to create condition instance!');
        }

        parent::init();
    }

    /**
     * Creating an specific expression to apply to condition, order by.
     *
     * @return ExpressionInterface apply to `where` conditions.
     */
    abstract public function getExpression(): ExpressionInterface;

    /**
     * Get pretty searchable key via model with an alias of the table.
     *
     * @return string the searchable key name
     */
    protected function searchableKey(): string
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $modelClass = $this->query->modelClass;
        list(, $alias) = $this->getTableNameAndAlias();

        return '{{' . $alias . '}}.[[' . $modelClass::searchableKey() . ']]';
    }

    /**
     * Returns the table name and the table alias for [[query::modelClass]].
     * This method extract from \yii\db\ActiveQuery.
     *
     * @return array the table name and the table alias.
     */
    private function getTableNameAndAlias(): array
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $query = $this->query;
        $modelClass = $query->modelClass;

        if (empty($query->from)) {
            $tableName = $modelClass::tableName();
        } else {
            $tableName = '';
            // if the first entry in "from" is an alias-tablename-pair return it directly
            foreach ($query->from as $alias => $tableName) {
                if (is_string($alias)) {
                    return [$tableName, $alias];
                }
                break;
            }
        }

        if (preg_match('/^(.*?)\s+({{\w+}}|\w+)$/', $tableName, $matches)) {
            $alias = $matches[2];
        } else {
            $alias = $tableName;
        }

        return [$tableName, $alias];
    }

}
