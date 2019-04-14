<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-tntsearch
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use yii\base\Behavior as BaseBehavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;

/**
 * Class Behavior
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Behavior extends BaseBehavior
{

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
     * Record after save event
     *
     * @param AfterSaveEvent $event triggered
     */
    public function afterSave(AfterSaveEvent $event)
    {

    }

    public function afterDelete()
    {

    }

}
