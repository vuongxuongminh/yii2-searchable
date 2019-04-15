<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\search;

use Yii;

/**
 * Class SearchableBehaviorTest
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class SearchableBehaviorTest extends TestCase
{

    public function testSync()
    {
        $model = Model::findOne(1);
        $model->article = 'testSync';
        $model->save(false);

        $models = Model::search('testSync')->all();
        $this->assertEquals(1, count($models));
    }

    public function testUnSync()
    {
        Model::withoutSyncingToSearch(function () {
            $model = Model::findOne(1);
            $model->article = 'testUnSync';
            $model->save(false);

            $models = Model::search('testUnSync')->all();
            $this->assertEquals(0, count($models));
        });
    }

    public function testShouldBeSearchable()
    {
        $model = Model::findOne(1);
        $model->shouldBeSearchable = false;
        $model->article = 'testShouldBeSearchable';
        $model->save(false);

        $models = Model::search('testShouldBeSearchable')->all();
        $this->assertEquals(0, count($models));
    }

    public function testDeleteSync()
    {
        $model = new Model([
            'title' => 'testDeleteSync title',
            'article' => 'testDeleteSync article'
        ]);

        $model->save(false);
        $modelSearch = Model::search('testDeleteSync title')->one();
        $this->assertNotNull($modelSearch);
        $modelSearch->delete();
        $modelSearch = Model::search('testDeleteSync title')->one();
        $this->assertNull($modelSearch);
    }

    public function testDeleteUnSync()
    {
        $model = new Model([
            'title' => 'testDeleteUnSync title',
            'article' => 'testDeleteUnSync article'
        ]);

        $model->save(false);
        $modelSearch = Model::search('testDeleteUnSync title')->one();
        $this->assertNotNull($modelSearch);

        Model::withoutSyncingToSearch(function () use ($model) {
            $model->delete();
            $modelSearch = Model::searchIds('testDeleteUnSync title');
            $this->assertNotEmpty($modelSearch);
        });
    }
}
