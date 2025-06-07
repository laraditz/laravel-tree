# Laravel Tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/laravel-tree.svg?style=flat-square)](https://packagist.org/packages/laraditz/laravel-tree)
[![Build Status](https://img.shields.io/travis/laraditz/laravel-tree/master.svg?style=flat-square)](https://travis-ci.org/laraditz/laravel-tree)
[![Quality Score](https://img.shields.io/scrutinizer/g/laraditz/laravel-tree.svg?style=flat-square)](https://scrutinizer-ci.com/g/laraditz/laravel-tree)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/laravel-tree.svg?style=flat-square)](https://packagist.org/packages/laraditz/laravel-tree)

Hierarchical or tree database implementation using path enumeration model.

## Installation

You can install the package via composer:

```bash
composer require laraditz/laravel-tree
```

## Configuration

Add the tree columns to your table by adding `addLaravelTreeColumns` to your database migration file.
For example, we add the tree columns to the `trees` table as shown below.

```php
 Schema::create('trees', function (Blueprint $table) {
    ...
    $table->addLaravelTreeColumns();
    ...
});
```

Then, add the `HasTreeNode` to the model.

```php
use Laraditz\LaravelTree\HasTreeNode;

class Tree extends Model
{
    use HasTreeNode;

}
```

## Usage

Create node as root.

```php
Tree::create([
    'user_id' => 1
])->asRoot()
```

Create node as child.

```php
// $tree is the parent object
Tree::create([
    'user_id' => 2
])->asChildOf($tree);

// or
$tree->appendChild([
    'user_id' => 2
]);
```

## Available Relationships

Below are all relationships under the `HasTreeNode`.
| Relationship name | Description  
|---------------------------|---------------------------------|
| parent() | Parent of current node. (1-1)
| child() | Children of current node. (1-N)

## Available Attributes

Below are all attributes under the `HasTreeNode`.
| Attribute name | Description  
|---------------------------|---------------------------------|
| child_count | Get number of children.
| direct_child_count | Get number of cirect children.
| has_child | Check if children exists.

## Available Methods

Below are all methods under the `HasTreeNode`.
| Method name | Description  
|---------------------------|---------------------------------|
| getChildCount() | Count number of children.  
| getChildCount(int $level) | Count number of children based on level.  
| getDirectChildCount()     | Count number of cirect children.
| getParentIds()            | Get parent ids from bottom-up.
| moveNode($node) | Move node to new parent $node.
| isChildOf($node) | Check is current node is child or distinct child of $node.
| isSameTree($node) | Check is current node is from the same tree as $node.
| isRootNode()              | Check if current node is a root node.
| isSameNode($node) | Compare two nodes is the same or not.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email raditzfarhan@gmail.com instead of using the issue tracker.

## Credits

-   [Raditz Farhan](https://github.com/laraditz)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
