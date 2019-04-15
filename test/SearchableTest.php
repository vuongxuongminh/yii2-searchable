<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\search;

use Yii;

/**
 * Class SearchableTest
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class SearchableTest extends TestCase
{

    public function testBootable()
    {
        $this->assertNotNull(Yii::$app->get('searchable', false));
        $this->assertTrue(isset(Yii::$app->controllerMap['searchable']));
    }

    public function testMakeSearchable()
    {
        Model::deleteAllFromSearch();
        Model::makeAllSearchable();

        $this->assertTrue(file_exists(Model::getSearchable()->storagePath . '/articles.index'));
    }

    public function testDeleteAllFromSearch()
    {
        Model::makeAllSearchable();
        Model::deleteAllFromSearch();

        $this->assertFalse(file_exists(Model::getSearchable()->storagePath . '/articles.index'));
    }

    public function testSearchMode()
    {
        Model::deleteAllFromSearch();
        Model::makeAllSearchable();

        $booleanIds = Model::searchIds('romeo or hamlet', 'boolean');
        $fuzzyIds = Model::searchIds('romeo or hamlet', 'fuzzy', ['fuzziness' => true]);

        $this->assertNotEquals($fuzzyIds, $booleanIds);
    }

}
