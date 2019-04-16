<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-search
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\search;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApp;

use vxm\search\console\CommandController;

/**
 * Class Bootstrap boot searchable component and console searchable controller
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Bootstrap implements BootstrapInterface
{

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function bootstrap($app)
    {
        if (!$app->get('searchable', false)) {
            $app->set('searchable', ['class' => Searchable::class]);
        }

        if ($app instanceof ConsoleApp && !isset($app->controllerMap['searchable'])) {
            $app->controllerMap['searchable'] = CommandController::class;
        }
    }

}
