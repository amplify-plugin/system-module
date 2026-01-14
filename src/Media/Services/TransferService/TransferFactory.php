<?php

namespace Amplify\System\Media\Services\TransferService;

class TransferFactory
{
    /**
     * @return ExternalTransfer|LocalTransfer
     */
    public static function build($disk, $path, $clipboard)
    {
        if ($disk !== $clipboard['disk']) {
            return new ExternalTransfer($disk, $path, $clipboard);
        }

        return new LocalTransfer($disk, $path, $clipboard);
    }
}
