<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2023 André Elvan
 */

namespace spacecatninja\imagekittransformer\models;

use craft\base\Model;

class ImagekitProfile extends Model
{
    public string $urlEndpoint = '';
    public bool $isWebProxy = false;
    public bool $useCloudSourcePath = false;
    public bool $getExternalImageDimensions = true;
    public array $defaultParams = [];
    public array|string $addPath = [];
}
