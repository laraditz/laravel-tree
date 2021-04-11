<?php

namespace Laraditz\LaravelTree;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

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
        $this->child()->updateOrCreate(Arr::add($items, $this->getParentIdColumn(), $this->id), []);

        return $this;
    }

    /**
     * Create as child of $parent
     * 
     * @param Model $parent
     *
     * @return $this
     */
    public function asChildOf($parent): self
    {
        $this->setParent($parent);
        $this->save();

        $this->refresh();

        return $this;
    }

    /**
     * Move Node to a new parent node.
     * 
     * @param $parent
     *
     * @return int|null
     */
    public function moveNode($parent): ?int
    {
        if ($parent->isChildOf($this)) {
            throw new LogicException('Cannot move node to child node.');
        }

        if ($this->isSameNode($parent)) {
            throw new LogicException('Cannot move to the same node.');
        }

        if ($this->isRootNode()) {
            throw new LogicException('Cannot move root node.');
        }

        if ($parent->id == $this->parent_id) {
            throw new LogicException('Nothing to do.');
        }

        $original_path = $this->{$this->getTreePathColumn()};
        $new_path = $parent->{$this->getTreePathColumn()} . $this->getTreeDelimiter();
        $replace_path = Str::beforeLast($original_path, $this->getTreeDelimiter() . $this->id) . $this->getTreeDelimiter();

        $update = DB::transaction(
            function () use ($parent, $original_path, $replace_path, $new_path) {

                $new_path_value = preg_replace_array('/:[a-z_]+/', [$new_path, $this->getTreePathColumn(), $replace_path, $this->getTreePathColumn(), $replace_path], 'CONCAT(":new_path", substring(:col_name, locate(":replace_path", :col_name)+length(":replace_path")))');
                $new_depth_value = $this->getDepthColumn() . ' - ' . (count(explode($this->getTreeDelimiter(), $replace_path)) - 1) . ' + ' . $parent->{$this->getDepthColumn()};

                $update = self::where($this->getTreePathColumn(), 'LIKE', $original_path . "%")
                    ->update([
                        // $this->getTreePathColumn() => DB::raw("REPLACE(" . $this->getTreePathColumn() . ", '" . $replace_path . "', '" . $new_path . "')"), // replace everything matches not just the first occurance
                        $this->getTreePathColumn() => DB::raw($new_path_value),
                        $this->getDepthColumn() => DB::raw($new_depth_value),
                    ]);

                $this->{$this->getParentIdColumn()} = $parent->id;
                $this->save();

                return $update;
            }
        );

        return $update;
    }

    /**
     * Get number of children  
     *
     * @return $int
     */
    public function getChildCountAttribute(): int
    {
        return $this->getChildCount();
    }

    /**
     * Get number of direct children  
     *
     * @return $int
     */
    public function getDirectChildCountAttribute(): int
    {
        return $this->getDirectChildCount();
    }

    /**
     * Check children exists.
     *
     * @return boolean
     */
    public function getHasChildAttribute(): bool
    {
        return $this->getChildCount() ? true : false;
    }

    /**
     * Count number of children  
     *
     * @return $int
     */
    public function getChildCount()
    {
        return $this->where($this->getTreePathColumn(), 'LIKE', $this->{$this->getTreePathColumn()} . $this->getTreeDelimiter() . '%')->count();
    }

    /**
     * Count number of direct children  
     *
     * @return $int
     */
    public function getDirectChildCount(): int
    {
        return $this->where($this->getParentIdColumn(), $this->id)->count();
    }

    /**
     * Return parent ids from bottom-up
     *
     * @return array|null
     */
    public function getParentIds(int $level = 1): ?array
    {
        $tree_path = $this->{$this->getTreePathColumn()};
        if (!$tree_path) return null;

        $collection = collect(explode($this->getTreeDelimiter(), $tree_path));
        $collection->pop(); // remove self

        if ($collection->count() <= 0) return null;

        $reversed = $collection->reverse()->values(); // bottom-up

        $filtered = $reversed->reject(function ($value, $key) use ($level) {
            return $key >= $level;
        });

        return $filtered->all();
    }

    /**
     * Check is current model is child or distinct child of parent
     *
     * @return boolean
     */
    public function isChildOf(self $parent): bool
    {
        if ($this->id === $parent->id) return false;

        return Str::contains($this->{$this->getTreePathColumn()}, $parent->{$this->getTreePathColumn()});
    }

    /**
     * Check is current model is from the same tree as node
     *
     * @return boolean
     */
    public function isSameTree(self $node): bool
    {
        return Str::contains($this->{$this->getTreePathColumn()}, $node->{$this->getTreePathColumn()})
            || Str::contains($node->{$this->getTreePathColumn()}, $this->{$this->getTreePathColumn()});
    }

    /**
     * Check if current node is a root node
     *
     * @return boolean
     */
    public function isRootNode(): bool
    {
        return $this->{$this->getParentIdColumn()} === null && $this->{$this->getDepthColumn()} === 1;
    }

    /**
     * Check if current node is same as given node
     *
     * @return boolean
     */
    public function isSameNode(self $node): bool
    {
        return $this->id === $node->id;
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
    public function child()
    {
        return $this->hasMany(self::class, $this->getParentIdColumn(), 'id');
    }
}
