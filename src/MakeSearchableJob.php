<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

/**
 * Class MakeSearchableJob support make searchable index data via worker
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class MakeSearchableJob extends QueueJob
{

    /**
     * @inheritDoc
     */
    protected function resolve(array $models): void
    {
        if (!empty($models)) {

            $modelClass = get_class(current($models));
            $modelClass::getSearchable()->createSearcher($modelClass::getDb())->upsert($models);
        }
    }

}
