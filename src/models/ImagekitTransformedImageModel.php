<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2023 André Elvan
 */

namespace spacecatninja\imagekittransformer\models;

use craft\elements\Asset;
use spacecatninja\imagerx\helpers\ImagerHelpers;
use spacecatninja\imagerx\models\BaseTransformedImageModel;
use spacecatninja\imagerx\models\LocalSourceImageModel;
use spacecatninja\imagerx\models\TransformedImageInterface;

class ImagekitTransformedImageModel extends BaseTransformedImageModel implements TransformedImageInterface
{
    private ImagekitProfile|null $profileConfig;

    /**
     * ImgixTransformedImageModel constructor.
     */
    public function __construct(string $imageUrl = null, Asset|string $source = null, array $transform = [], ?ImagekitProfile $profileConfig = null)
    {
        $this->profileConfig = $profileConfig;
        
        if ($imageUrl !== null) {
            $this->url = $imageUrl;
        }
        
        $crop = $transform['crop'] ?? 'maintain_ratio';
        $cropMode = $transform['cropMode'] ?? null;

        if (isset($transform['width'], $transform['height'])) {
            $this->width = (int)$transform['width'];
            $this->height = (int)$transform['height'];

            [$sourceWidth, $sourceHeight] = $source !== null ? $this->getSourceImageDimensions($source) : [0, 0];
            $transformW = (int)$transform['width'];
            $transformH = (int)$transform['height'];

            if ($sourceWidth !== 0 && $sourceHeight !== 0) {
                if ($crop === 'at_max' || $crop === 'at_max_enlarge') {
                    // The image is fitted inside the size, and only enlarged with at_max_enlarge
                    if ($sourceWidth / $sourceHeight > $transformW / $transformH) {
                        $useW = $crop === 'at_max_enlarge' ? $transformW : min($transformW, $sourceWidth);
                        $this->width = $useW;
                        $this->height = round($useW * ($sourceHeight / $sourceWidth));
                    } else {
                        $useH = $crop === 'at_max_enlarge' ? $transformH : min($transformH, $sourceHeight);
                        $this->width = round($useH * ($sourceWidth / $sourceHeight));
                        $this->height = $useH;
                    }
                } elseif ($cropMode === 'extract') {
                    // An extracted area is never larger than the source
                    $this->width = min($transformW, $sourceWidth);
                    $this->height = min($transformH, $sourceHeight);
                }
            }
        } else if (isset($transform['width']) || isset($transform['height'])) {
            [$sourceWidth, $sourceHeight] = $source !== null ? $this->getSourceImageDimensions($source) : [0, 0];

            if ($sourceWidth !== 0 && $sourceHeight !== 0) {
                [$w, $h] = $this->calculateTargetSize($transform, $sourceWidth, $sourceHeight);

                // at_max never enlarges the image
                if ($crop === 'at_max' && ($w > $sourceWidth || $h > $sourceHeight)) {
                    [$w, $h] = [$sourceWidth, $sourceHeight];
                }

                $this->width = $w;
                $this->height = $h;
            } else {
                // Without the source's size, only the size that was asked for is known
                $this->width = (int)($transform['width'] ?? 0);
                $this->height = (int)($transform['height'] ?? 0);
            }
        } else {
            // Neither is set, image is not resized. Just get dimensions and return.
            [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);

            $this->width = $sourceWidth;
            $this->height = $sourceHeight;
        }
    }

    /**
     * Get source dimensions, either from an asset, or an external image.
     */
    protected function getSourceImageDimensions($source): array
    {
        if ($source instanceof Asset) {
            return [(int)$source->getWidth(), (int)$source->getHeight()];
        }

        if ($this->profileConfig !== null && $this->profileConfig->getExternalImageDimensions) {
            try {
                $sourceModel = new LocalSourceImageModel($source);
                $sourceModel->getLocalCopy();
    
                $sourceImageSize = ImagerHelpers::getSourceImageSize($sourceModel);
    
                return [(int)$sourceImageSize[0], (int)$sourceImageSize[1]];
            } catch (\Throwable) {
                \Craft::error('Could not get dimensions of external image', __METHOD__);
            }
        }

        return [0, 0];
    }

    /**
     * Calculate target size
     */
    protected function calculateTargetSize(array $transform, int $sourceWidth, int $sourceHeight): array
    {
        $ratio = $sourceWidth / $sourceHeight;

        $w = $transform['width'] ?? null;
        $h = $transform['height'] ?? null;

        if ($w) {
            return [$w, round($w / $ratio)];
        }
        if ($h) {
            return [round($h * $ratio), $h];
        }

        return [0, 0];
    }
    
}
