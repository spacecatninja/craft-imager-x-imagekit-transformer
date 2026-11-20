<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImagekitTransformer\Data;

use CraftCms\Cms\Component\Component;

/**
 * The config of one ImageKit profile, from the `profiles` setting.
 */
class ImagekitProfile extends Component
{
    public string $urlEndpoint = '';
    public bool $isWebProxy = false;
    public bool $useCloudSourcePath = false;
    public bool $getExternalImageDimensions = true;
    public array $defaultParams = [];
    public array|string $addPath = [];
}
