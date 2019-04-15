# Yii2 Full-Text Search

[![Latest Stable Version](https://poser.pugx.org/vxm/yii2-search/v/stable)](https://packagist.org/packages/vxm/yii2-search)
[![Total Downloads](https://poser.pugx.org/vxm/yii2-search/downloads)](https://packagist.org/packages/vxm/yii2-search)
[![Build Status](https://travis-ci.org/vuongxuongminh/yii2-search.svg?branch=master)](https://travis-ci.org/vuongxuongminh/yii2-search)
[![Code Coverage](https://scrutinizer-ci.com/g/vuongxuongminh/yii2-search/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/vuongxuongminh/yii2-search/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vuongxuongminh/yii2-search/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vuongxuongminh/yii2-search/?branch=master)
[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](http://www.yiiframework.com/)

## About it

An extension provide simple full-text search with ideas get from [laravel/scout](https://github.com/laravel/scout) and base on [teamtnt/tntsearch](https://github.com/teamtnt/tntsearch) wrapper for Yii2 Active Record.

## Requirements

* [PHP >= 7.1](http://php.net)
* [yiisoft/yii2 >= 2.0.14.2](https://github.com/yiisoft/yii2)

## Installation

Require Yii2 Search using [Composer](https://getcomposer.org):

```bash
composer require vxm/yii2-search
```

Finally, add the `\vxm\searchable\SearchableTrait` trait and attach `vxm\searchable\SearchableBehavior` to the active record you would like to make searchable. This will help sync the model with index data

```php
use vxm\search\SearchableBehavior;
use vxm\search\SearchableTrait;

class Article extends ActiveRecord
{

    use SearchableTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'searchable' => SearchableBehavior::class
        ];
    }


}
```

### Queueing

While not strictly required to use this extension, you should strongly consider configuring a [yii2-queue](https://github.com/yiisoft/yii2-queue) 
before using an extension. Running a queue worker will allow it to queue all operations that sync your model information to your search indexes, 
providing much better response times for your application's web interface.

Once you have configured a queue component, set the value of the queue option in your application configuration file to component id of it 
or an array config of it.

```php
'components' => [
    'searchable' => [
        'class' => 'vxm\search\Searchable',
        'queue' => 'queueComponentId'
    ]
]
```

## Configuration

### Configuring Model Index

Each Active Record model is synced with a given search `index`, which contains all of the searchable records for that model. 
In other words, you can think of each index like a MySQL table. By default, each model will be persisted to an index matching the model's typical `table` name. 
Typically, this is the plural form of the model name; however, you are free to customize the `index` name by overriding the `searchableIndex` static method on the Active Record model class:

```php
use vxm\search\SearchableBehavior;
use vxm\search\SearchableTrait;

class Article extends ActiveRecord
{

    use SearchableTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'searchable' => SearchableBehavior::class
        ];
    }

    /**
     * Get the index name for the model class.
     *
     * @return string
     */
    public static function searchableIndex(): string
    {
        return 'articles_index';
    }

}
```

### Configuring Searchable Data

By default, the entire `toArray` form of a given model will be persisted to its search index. 
If you would like to customize the data that is synchronized to the search index, 
you may override the `toSearchableArray` method on the model:

```php
use vxm\search\SearchableBehavior;
use vxm\search\SearchableTrait;

class Article extends ActiveRecord
{

    use SearchableTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'searchable' => SearchableBehavior::class
        ];
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        // Customize array...

        return $array;
    }

}
```

### Configuring Searchable Key

By default, the primary key name of the model as the unique ID stored in the search index. 
If you need to customize this behavior, you may override the `searchableKey` static method on the model:

```php
use vxm\search\SearchableBehavior;
use vxm\search\SearchableTrait;

class Article extends ActiveRecord
{

    use SearchableTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'searchable' => SearchableBehavior::class
        ];
    }

    /**
     * Get searchable key by default primary key will be use.
     *
     * @return string|int key name.
     */
    public static function searchableKey()
    {
        $array = $this->toArray();

        // Customize array...

        return $array;
    }

}
```

## Indexing

### Batch Import

If you are installing an extension into an existing project, you may already have database records you need to import into your search driver. 
This extension provides an import action that you may use to import all of your existing records into your search indexes:

```php
php yii searchable/import --models="app\models\Post"
```

You can import multi model classes by separator `,`:

```php
php yii searchable/import --models="app\models\Post, app\models\Category"
```

### Adding Records

Once you have added the `vxm\search\SearchableTrait` and attached the `vxm\search\SearchableBehavior` behavior to a model, 
all you need to do is save a model instance and it will automatically be added to your search index. 
If you have configured queue this operation will be performed in the background by your queue worker:

```php
$post = new \app\models\Post;

// ...

$post->save();
```

### Adding Via Active Query Result

If you would like to add a Active Query result to your search index, you may use `makeSearchable` method onto an Active Query result. 
The `makeSearchable` method will chunk the results of the query and add the records to your search index. 
Again, if you have configured queue, all of the chunks will be added in the background by your queue workers:

```php
// Adding via Active Query result...
$models = \app\models\Post::find()->where(['author_id' => 1])->all();

\app\models\Post::makeSearchable($models);
```

The `makeSearchable` method can be considered an `upsert` operation. In other words, if the model record is already in your index, it will be updated. 
If it does not exist in the search index, it will be added to the index.

### Updating Records

To update a searchable model, you only need to update the model instance's properties and save the model to your database. 
This extension will automatically persist the changes to your search index:

```php
$post = \app\models\Post::findOne(1);

// Update the post...

$post->save();
```

You may also use the `makeSearchable` method on an Active Record class to update instance. 
If the models do not exist in your search index, they will be created:

```php
// Updating via Active Query result...
$models = \app\models\Post::find()->where(['author_id' => 1])->all();

\app\models\Post::makeSearchable($models);
```

### Removing Records

To remove a record from your index, delete the model from the database:

```php
$post = \app\models\Post::findOne(1);

$post->delete();
```

If you would like to delete a Active Query result from your search index, you may use the `deleteSearchable` method on an Active Record class:

```php
// Deleting via Active Query result...
$models = \app\models\Post::find()->where(['author_id' => 1])->all();

\app\models\Post::deleteSearchable($models);
```

### Pausing Indexing

Sometimes you may need to perform a batch of Active Record operations on a model without syncing the model data to your search index. 
You may do this using the `withoutSyncingToSearch` method. This method accepts a single callback which will be immediately executed. 
Any model operations that occur within the callback will not be synced to the model's index:

```php
\app\models\Post::withoutSyncingToSearch(function () {
   $post = \app\models\Post::findOne(1);
   $post->save(); // will not syncing with index data
});
```

### Conditionally Searchable Model Instances

Sometimes you may need to only make a model searchable under certain conditions. For example, imagine you have `app\models\Article` model that may be in one of two states: `draft` and `published`. 
You may only want to allow `published` posts to be searchable. To accomplish this, you may define a `shouldBeSearchable` method on your model:

```php
use vxm\search\SearchableBehavior;
use vxm\search\SearchableTrait;

class Article extends ActiveRecord
{

    use SearchableTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'searchable' => SearchableBehavior::class
        ];
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public static function shouldBeSearchable()
    {
        return $this->is_published;
    }

}
```

The `shouldBeSearchable` method is only applied when manipulating models through the save method. 
Directly making models using the `searchable` or `makeSearchable` method will override the result of the `shouldBeSearchable` method:

```php
// Will respect "shouldBeSearchable"...
$post = \app\models\Post::findOne(1);

$post->save();

// Will override "shouldBeSearchable"...
$post->searchable();

$models = \app\models\Post::find()->where(['author_id' => 1])->all();

\app\models\Post::makeSearchable($models);
```

## Searching

You may begin searching a model using the `search` method. The search method accepts a single string that will be used to search your models. 
This method return an `ActiveQuery` you can add more condition or relationship like an origin query. 

> Note when add more query condition you must not be use `where` method use `andWhere` or `orWhere` instead because it will override search ids condition result.

```php
$posts = \app\models\Post::search('vxm')->all();
$posts = \app\models\Post::search('vxm')->andWhere(['author_id' => 1])->all();


// not use
$posts = \app\models\Post::search('vxm')->where(['author_id' => 1])->all();
```

You can choice a `boolean` or `fuzzy` search mode:

```php
$posts = \app\models\Post::search('vxm', 'fuzzy', ['fuzziness' => true])->all();
$posts = \app\models\Post::search('vxm', 'boolean')->all();
```

For more detail of search mode please refer to [teamtnt/tntsearch](https://github.com/teamtnt/tntsearch) to see full document.
