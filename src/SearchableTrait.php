<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use Yii;

/**
 * Trait SearchableTrait
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
trait SearchableTrait
{

    /**
     * @param string $query
     * @param array $config
     * @param string|null $mode
     * @return \yii\db\ActiveQuery
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public static function search(string $query, array $config = [], ?string $mode = null)
    {
        /** @var Searcher $searcher */
        $searcher = static::getSearchable()->createSearcher($config, $mode, static::getDb());
        $result = $searcher->search($query, static::searchableIndex());

        /** @var \yii\db\ActiveQuery $aq */
        $aq = static::find();

        if (empty($result['ids'])) {

            return $aq->andWhere('1 = 0');
        } else {
            /** @var \yii\db\Connection $db */
            $db = static::getDb();
            $db->setQueryBuilder([
                'expressionBuilders' => [
                    SearchableExpression::class => SearchableExpressionBuilder::class
                ]
            ]);
            $searchableExpression = new SearchableExpression([
                'query' => $aq,
                'ids' => $result['ids']
            ]);
            $aq->where = ['AND', $searchableExpression, $aq->where];

            return $aq;
        }

    }

    public static function searchableIndex(): string
    {
        return static::getDb()->quoteSql(static::tableName());
    }

    public static function searchableFields(): array
    {
        return static::getTableSchema()->columns;
    }

    public function shouldBeSearchable()
    {
        return true;
    }

    /**
     * @return Searchable
     * @throws \yii\base\InvalidConfigException
     */
    public static function getSearchable(): Searchable
    {
        return Yii::$app->get('searchable');
    }

}
