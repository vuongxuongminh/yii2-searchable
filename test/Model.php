<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-searchable
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\searchable;

use vxm\searchable\SearchableBehavior;
use yii\db\ActiveRecord;

use vxm\searchable\SearchableTrait;

/**
 * Class Model
 *
 * @property int $id
 * @property string $title
 * @property string $article
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Model extends ActiveRecord
{

    use SearchableTrait;

    public $shouldBeSearchable = true;

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return 'articles';
    }

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'searchable' => SearchableBehavior::class
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->shouldBeSearchable;
    }

}
