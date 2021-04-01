<?php

namespace Laraditz\LaravelTree;

use Illuminate\Support\Arr;

trait TreeNodeTrait
{
    /**
     * Get the parent id column name.
     *
     * @return  string
     */
    public function getParentIdColumn(): string
    {
        return LaravelTree::COLUMN_PARENT_ID;
    }

    /**
     * Get the tree path column name.
     *
     * @return  string
     */
    public function getTreePathColumn(): string
    {
        return LaravelTree::COLUMN_TREE_PATH;
    }

    /**
     * Get the depth column name.
     *
     * @return  string
     */
    public function getDepthColumn(): string
    {
        return LaravelTree::COLUMN_DEPTH;
    }

    /**
     * Get the depth column name.
     *
     * @return  string
     */
    public function getSortNoColumn(): string
    {
        return LaravelTree::COLUMN_SORT_NO;
    }

    public function getTreeDelimiter(): string
    {
        return config('laraveltree.delimiter');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->{$model->getSortNoColumn()} = $model->max('sort_no') + 1;
        });

        static::created(function ($model) {
            $model->fillNodeColumns(true);
        });

        static::updating(function ($model) {
            $model->fillNodeColumns();
        });
    }

    private function fillNodeColumns($save = false)
    {
        if ($this->{$this->getParentIdColumn()}) {
            $tree_path = $this->parent->tree_path . $this->getTreeDelimiter() . $this->id;

            $this->fill([
                $this->getTreePathColumn() => $tree_path,
                $this->getDepthColumn() => count(explode($this->getTreeDelimiter(), $tree_path)),
            ]);

            if ($save === true) {
                $this->save();
            }
        }
    }

    /**
     * Apply parent model.
     *
     * @param Model|null $value
     *
     * @return $this
     */
    protected function setParent($value): self
    {
        $this->setParentId($value ? $value->getKey() : null);

        return $this;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setParentId($value): self
    {
        $this->attributes[$this->getParentIdColumn()] = $value;

        return $this;
    }

    /**
     * Create as root with null parent.
     *     
     *
     * @return $this
     */
    public function asRoot(): self
    {
        $this->fill([
            $this->getParentIdColumn() => null,
            $this->getTreePathColumn() => $this->id,
            $this->getDepthColumn() => 1,
        ])->save();

        $this->refresh();

        return $this;
    }

    /**
     * Append as child of current model.
     * 
     * @param array $items
     *
     * @return $this
     */
    public function appendChild(array $items): self
    {
        $this->children()->updateOrCreate(Arr::add($items, $this->getParentIdColumn(), $this->id), []);

        return $this;
    }

    /**
     * Create as child of $parent
     * 
     * @param Model $parent
     *
     * @return $this
     */
    public function asChildOf($parent)
    {
        $this->setParent($parent);
        $this->save();

        $this->refresh();

        return $this;
    }

    /**
     * Get number of children  
     *
     * @return $int
     */
    public function getChildrenCountAttribute(): int
    {
        return $this->getChildrenCount();
    }

    /**
     * Get number of direct children  
     *
     * @return $int
     */
    public function getDirectChildrenCountAttribute(): int
    {
        return $this->getDirectChildrenCount();
    }

    /**
     * Check childrent exists.
     *
     * @return boolean
     */
    public function getHasChildrenAttribute(): bool
    {
        return $this->getChildrenCount() ? true : false;
    }

    /**
     * Count number of children  
     *
     * @return $int
     */
    public function getChildrenCount()
    {
        return $this->where($this->getTreePathColumn(), 'LIKE', $this->{$this->getTreePathColumn()} . $this->getTreeDelimiter() . '%')->count();
    }

    /**
     * Count number of direct children  
     *
     * @return $int
     */
    public function getDirectChildrenCount()
    {
        return $this->where($this->getParentIdColumn(), $this->id)->count();
    }

    /**
     * Get parent through relationship
     *
     * @return Model
     */
    public function parent()
    {
        return $this->belongsTo(self::class, $this->getParentIdColumn(), 'id');
    }

    /**
     * Get children through relationship
     *
     * @return Model
     */
    public function childrens()
    {
        return $this->hasMany(self::class, $this->getParentIdColumn(), 'id');
    }
}
