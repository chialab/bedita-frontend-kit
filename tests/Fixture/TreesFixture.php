<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\TreesFixture as BETreesFixture;

/**
 * Trees test fixture.
 */
class TreesFixture extends BETreesFixture
{
    public function init(): void
    {
        $trees = [
            2 => [
                4 => [
                    8 => [
                        10 => [],
                        12 => [],
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

    private function transformBranch($branch, &$records = null, $parent = null, &$count = 0): void
    {
        if ($records === null) {
            $records = [];
        }

        $parentNodeId = $count;
        $priority = 0;
        foreach ($branch as $id => $child) {
            $count++;
            $entry = [
                'object_id' => $id,
                'parent_id' => $parent ? $parent['object_id'] : null,
                'root_id' => $parent ? $parent['root_id'] : $id,
                'parent_node_id' => $parent ? $parentNodeId : null,
                'priority' => ++$priority,
                'menu' => 1,
                'canonical' => 0,
                'slug' => sprintf('slug-%d', $id),
            ];

            $records[] = $entry;

            $this->transformBranch($child, $records, $entry, $count);
        }
    }
}
