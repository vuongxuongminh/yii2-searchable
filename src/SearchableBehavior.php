<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-searchable
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\searchable;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class Behavior support syncing record event with indexing data.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class SearchableBehavior extends Behavior
{

    /**
     * @var \yii\db\ActiveRecord
     * @inheritDoc
     */
    public $owner;

    /**
     * The class names that syncing is disabled for.
     *
     * @var string[]
     */
    protected static $syncingDisabledFor = [];

    /**
     * @inheritDoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    /**
     * Handle the saved event for the model.
     */
    public function afterSave()
    {
        $model = $this->owner;

        if (static::syncingDisabledFor($model)) {
            return;
        }

        if (!$model->shouldBeSearchable()) {
            $model->unsearchable();

            return;
        }

        $model->searchable();
    }

    /**
     * Handle the deleted event for the model.
     */
    public function afterDelete(): void
    {
        $model = $this->owner;

        if (static::syncingDisabledFor($model)) {
            return;
        }

        $model->unsearchable();
    }

    /**
     * Enable syncing for the given class.
     *
     * @param string $class of records need to enable syncing.
     */
    public static function enableSyncingFor($class): void
    {
        unset(static::$syncingDisabledFor[$class]);
    }

    /**
     * Disable syncing for the given class.
     *
     * @param string $class of records need to disable syncing.
     */
    public static function disableSyncingFor($class): void
    {
        static::$syncingDisabledFor[$class] = true;
    }

    /**
     * Determine if syncing is disabled for the given class or model.
     *
     * @param object|string $class of records need to disable syncing.
     * @return bool weather syncing disabled.
     */
    public static function syncingDisabledFor($class): bool
    {
        $class = is_object($class) ? get_class($class) : $class;

        return isset(static::$syncingDisabledFor[$class]);
    }

}
