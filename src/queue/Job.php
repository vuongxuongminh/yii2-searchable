<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-searchable
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\searchable\queue;

use yii\queue\JobInterface;

/**
 * Class Job providing base methods need for index data jobs.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
abstract class Job implements JobInterface
{

    /**
     * @var array primary key for invoke records.
     */
    protected $ids = [];

    /**
     * QueueJob constructor.
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[] $models need to making searchable index data.
     */
    public function __construct($models)
    {
        $models = is_array($models) ? $models : [$models];

        foreach ($models as $model) {
            /** @var $model \yii\db\ActiveRecord */
            foreach ($model->getPrimaryKey(true) as $key => $value) {
                $this->ids[$key][] = $value;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        if (!empty($this->ids)) {
            /** @var \yii\db\ActiveRecord $modelClass */
            $models = $modelClass::findAll($this->ids);
            $this->resolve($models);
        }
    }

    /**
     * Solve models job.
     *
     * @param array|\yii\db\ActiveRecord[] $models need to be execute searchable index job.
     */
    abstract protected function resolve(array $models): void;
}
