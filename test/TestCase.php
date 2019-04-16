<?php
/**
 * @link https://github.com/yiiviet/yii2-payment
 * @copyright Copyright (c) 2017 Yii2VN
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\searchable;

use Yii;

use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Class TestCase
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class TestCase extends BaseTestCase
{


    public function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        $file = FileHelper::normalizePath(dirname(__DIR__) . '/vendor/teamtnt/tntsearch/tests/_files/articles.sqlite');

        new $appClass(ArrayHelper::merge([
            'id' => 'test',
            'basePath' => __DIR__,
            'bootstrap' => ['vxm\searchable\Bootstrap'],
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite:' . $file,
                    'username' => 'testUser',
                    'password' => 'testPass'
                ]
            ]
        ], $config));
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

}
