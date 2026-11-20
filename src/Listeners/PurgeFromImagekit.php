<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImagekitTransformer\Listeners;

use CraftCms\Cms\Asset\Events\AssetReplaced;
use SpaceCatNinja\ImagekitTransformer\Support\ImagekitHelpers;

/**
 * Purges an asset from ImageKit when its file is replaced.
 *
 * Registered when the `purgeEnabled` setting is on.
 */
class PurgeFromImagekit
{
    public function handleAssetReplaced(AssetReplaced $event): void
    {
        ImagekitHelpers::purgeAsset($event->asset);
    }
}
