<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use yii\console\Controller;

/**
 * Class CommandController support import and flush model classes.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class CommandController extends Controller
{
    /**
     * @var string models class name separate by `,`.
     */
    public $models = '';

    /**
     * @inheritDoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['models']);
    }

    /**
     * Import the given models into the search index.
     */
    public function actionImport()
    {
        $models = explode(',', $this->models);
        $models = array_filter($models);

        foreach ($models as $model) {
            $model = trim($model);
            $model::makeAllSearchable();

            $this->stdout('All [' . $model . '] records have been imported.');
        }
    }

    /**
     * Delete all of the models records from the index.
     */
    public function actionDeleteAll()
    {
        $models = explode(',', $this->models);
        $models = array_filter($models);

        foreach ($models as $model) {
            $model = trim($model);
            $model::deleteAllFromSearch();

            $this->stdout('All [' . $model . '] records have been flushed.');
        }
    }



}
