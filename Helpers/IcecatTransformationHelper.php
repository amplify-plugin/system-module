<?php

namespace Amplify\System\Helpers;

use Amplify\System\Utility\Models\IcecatDefinition;
use App\Models\Category;

class IcecatTransformationHelper
{
    /**
     * @return string[][]
     */
    public static function getAppliesToOptions(): array
    {
        return [
            ['name' => 'Products'],
            ['name' => 'Categories'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function getRunWhenOptions(): array
    {
        return [
            ['name' => 'Import - Create'],
            ['name' => 'Import - Update'],
            ['name' => 'Save'],
            ['name' => 'On Demand'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function getAddStepOptions(): array
    {
        return [
            [
                'name' => 'if-empty',
                'availableParam' => [1, 2],
            ],
            [
                'name' => 'if-matches',
                'availableParam' => [1, 2, 3],
            ],
            [
                'name' => 'convert-case',
                'availableParam' => [1, 2, 3],
            ],
            [
                'name' => 'store',
                'availableParam' => [1, 2, 3, 4],
            ],
            [
                'name' => 'extract',
                'availableParam' => [1, 2, 3],
            ],
            [
                'name' => 'find-sub-string',
                'availableParam' => [1, 2, 3, 4],
            ],
            [
                'name' => 'replace-sub-string',
                'availableParam' => [1, 2, 3, 4],
            ],
            [
                'name' => 'remove-sub-string',
                'availableParam' => [1, 2, 3, 4],
            ],
            [
                'name' => 'assign-to-cat',
                'availableParam' => [1, 2, 3],
            ],
            [
                'name' => 'assign-to-class',
                'availableParam' => [1, 2, 3],
            ],
        ];
    }

    public static function getCategoryName(int $categoryId): string
    {
        $category = Category::query()->find($categoryId);

        return $category->category_name ?? '';
    }

    public static function getTransformationNames()
    {
        $transformations = IcecatDefinition::all()->map(function ($item, $index) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
            ];
        });

        return $transformations;
    }

    /**
     * @return string[][]
     */
    public static function getAddStepParams(): array
    {
        return [
            [
                'placeholder' => 'Param 1',
                'value' => 1,
            ],
            [
                'placeholder' => 'Param 2',
                'value' => 2,
            ],
            [
                'placeholder' => 'Param 3',
                'value' => 3,
            ],
            [
                'placeholder' => 'Param 4',
                'value' => 4,
            ],
        ];
    }
}
