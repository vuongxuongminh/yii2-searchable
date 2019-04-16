<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-searchable
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\searchable;

use Yii;

use vxm\searchable\CommandController;

/**
 * Class CommandTest
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class CommandTest extends TestCase
{

    public function testImport()
    {
        Model::deleteAllFromSearch();
        /** @var \vxm\searchable\CommandController $controller */
        list($controller) = Yii::$app->createController('searchable');
        $controller->models = Model::class;
        $controller->runAction('import');
        $this->assertTrue(file_exists(Model::getSearchable()->storagePath . '/articles.index'));
    }

    public function testDeleteAll()
    {
        Model::makeAllSearchable();
        /** @var \vxm\searchable\CommandController $controller */
        list($controller) = Yii::$app->createController('searchable');
        $controller->models = Model::class;
        $controller->runAction('delete');
        $this->assertFalse(file_exists(Model::getSearchable()->storagePath . '/articles.index'));
    }

}
