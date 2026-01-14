<?php

namespace Amplify\System\Media\Traits;

trait PathTrait
{
    /**
     * Create path for new directory / file
     *
     *
     * @return string
     */
    public function newPath($path, $name)
    {
        if (! $path) {
            return $name;
        }

        return $path.'/'.$name;
    }

    /**
     * Rename path - for copy / cut operations
     *
     *
     * @return string
     */
    public function renamePath($itemPath, $recipientPath)
    {
        if ($recipientPath) {
            return $recipientPath.'/'.basename($itemPath);
        }

        return basename($itemPath);
    }

    /**
     * Transform path name
     *
     *
     * @return string
     */
    public function transformPath($itemPath, $recipientPath, $partsForRemove)
    {
        $elements = array_slice(explode('/', $itemPath), $partsForRemove);

        if ($recipientPath) {
            return $recipientPath.'/'.implode('/', $elements);
        }

        return implode('/', $elements);
    }
}
