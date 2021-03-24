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

    public function appendChild(array $items): self
    {
        $this->children()->updateOrCreate(Arr::add($items, $this->getParentIdColumn(), $this->id), []);

        return $this;
    }

    public function asChildOf($parent)
    {
        $this->setParent($parent);
        $this->save();

        $this->refresh();

        return $this;
    }

    public function getDownlineCountAttribute()
    {
        return $this->getDownlineCount();
    }

    public function getDirectDownlineCount()
    {
        return $this->where($this->getParentIdColumn(), $this->id)->count();
    }

    public function getDownlineCount()
    {
        return $this->where($this->getTreePathColumn(), 'LIKE', $this->{$this->getTreePathColumn()} . $this->getTreeDelimiter() . '%')->count();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, $this->getParentIdColumn(), 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, $this->getParentIdColumn(), 'id');
    }
}
