<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\TreesFixture as BETreesFixture;

/**
 * Trees test fixture.
 */
class TreesFixture extends BETreesFixture
{
    public function init()
    {
        $trees = [
            2 => [
                4 => [
                    8 => [
                        10 => [],
                    ],
                    9 => [],
                ],
                5 => [],
            ],
            3 => [
                6 => [],
                7 => [],
            ],
        ];

        $this->transformBranch($trees, $records);
        $this->records = $records;

        parent::init();
    }

    private function transformBranch($branch, &$records = null, $parent = null, &$count = 0)
    {
        if ($records === null) {
            $records = [];
        }

        $children = [];
        $left = ($parent ? $parent['tree_left'] : 0) + 1;
        $parentIndex = $count;
        foreach ($branch as $id => $child) {
            $index = $count++;
            $entry = [
                'object_id' => $id,
                'parent_id' => $parent ? $parent['object_id'] : null,
                'root_id' => $parent ? $parent['root_id'] : $id,
                'parent_node_id' => $parent ? $parentIndex : null,
                'tree_left' => $left,
                'tree_right' => $left + 1,
                'menu' => 1,
                'depth_level' => 0,
                'canonical' => 0,
            ];

            $records[] = $entry;

            if (!empty($branch)) {
                $desc = $this->transformBranch($child, $records, $entry, $count);
                if (empty($desc)) {
                    $left = $left + 2;
                } else {
                    $right = end($desc)['tree_right'] + 1;
                    $records[$index]['tree_right'] = $right;
                    $left = $right + 1;
                }
            }

            $children[] = $entry;
        }

        return $children;
    }
}