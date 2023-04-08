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
        
        if (isset($transform['width'], $transform['height'])) {
            $this->width = (int)$transform['width'];
            $this->height = (int)$transform['height'];

            if ($source !== null && ($crop === 'at_max' || $crop === 'at_max_enlarge')) {
                [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);
                
                // todo: enforce upscale limit if crop is at_max 

                $transformW = (int)$transform['width'];
                $transformH = (int)$transform['height'];

                if ($sourceWidth !== 0 && $sourceHeight !== 0) {
                    if ($sourceWidth / $sourceHeight > $transformW / $transformH) {
                        $useW = min($transformW, $sourceWidth);
                        $this->width = $useW;
                        $this->height = round($useW * ($sourceHeight / $sourceWidth));
                    } else {
                        $useH = min($transformH, $sourceHeight);
                        $this->width = round($useH * ($sourceWidth / $sourceHeight));
                        $this->height = $useH;
                    }
                }
            }
        } else if (isset($transform['width']) || isset($transform['height'])) {
            if ($source !== null && $transform !== null) {
                [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);
                [$w, $h] = $this->calculateTargetSize($transform, $sourceWidth, $sourceHeight);

                $this->width = $w;
                $this->height = $h;
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
            return [$source->getWidth(), $source->getHeight()];
        }

        if ($this->profileConfig !== null && $this->profileConfig->getExternalImageDimensions) {
            try {
                $sourceModel = new LocalSourceImageModel($source);
                $sourceModel->getLocalCopy();
    
                $sourceImageSize = ImagerHelpers::getSourceImageSize($sourceModel);
    
                return [$sourceImageSize[0], $sourceImageSize[1]];
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
