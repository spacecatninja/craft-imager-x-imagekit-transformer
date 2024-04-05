<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2023 André Elvan
 */

namespace spacecatninja\imagekittransformer\transformers;

use Craft;
use craft\base\Component;
use craft\elements\Asset;

use spacecatninja\imagekittransformer\helpers\ImagekitHelpers;
use spacecatninja\imagekittransformer\ImagekitTransformer;
use spacecatninja\imagekittransformer\models\ImagekitProfile;
use spacecatninja\imagekittransformer\models\Settings;
use spacecatninja\imagekittransformer\models\ImagekitTransformedImageModel;

use spacecatninja\imagerx\models\ConfigModel;
use spacecatninja\imagerx\services\ImagerService;
use spacecatninja\imagerx\transformers\TransformerInterface;
use spacecatninja\imagerx\exceptions\ImagerException;

class Imagekit extends Component implements TransformerInterface
{

    public function transform(Asset|string $image, array $transforms): ?array
    {
        $transformedImages = [];

        foreach ($transforms as $transform) {
            $transformedImages[] = $this->getTransformedImage($image, $transform);
        }

        return $transformedImages;
    }

    /**
     * @throws \spacecatninja\imagerx\exceptions\ImagerException
     */
    private function getTransformedImage(Asset|string $image, array $transform): ?ImagekitTransformedImageModel
    {
        $config = ImagerService::getConfig();
        /** @var Settings $settings */
        $settings = ImagekitTransformer::$plugin->getSettings();
        
        if ($settings === null) {
            return null;
        }
        
        $profileName = $config->transformerConfig['profile'] ?? $settings->defaultProfile;
        $profile = ImagekitHelpers::getProfile($profileName);
        
        if (!$profile) {
            $msg = 'ImageKit profile “' . $profileName . '” does not exist.';
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        $imageKit = new \ImageKit\ImageKit(
            $settings->publicKey,
            $settings->privateKey,
            $profile->urlEndpoint
        );
        
        $params = $this->createParams($transform, $image, $profile, $settings);
        
        $url = $imageKit->url([
            'path' => ImagekitHelpers::getFilePath($image, $profile, $settings), 
            'transformation' => [$params],
            'signed' => $settings->signUrls,
            'expireSeconds' => $settings->signedUrlsExpireSeconds
        ]);

        return new ImagekitTransformedImageModel($url, $image, $params, $profile);
    }
    
    private function createParams(array $transform, Asset|string $image, ImagekitProfile $profile, Settings $settings): array
    {
        $config = ImagerService::getConfig();
        $transformerParams = $transform['transformerParams'] ?? [];
        
        $r = [];

        // Merge in default values from settings
        if (!empty($settings->defaultParams)) {
            $r = array_merge($r, $settings->defaultParams);
        }
        
        // Merge in default values from profile
        if (!empty($profile->defaultParams)) {
            $r = array_merge($r, $profile->defaultParams);
        }
        
        // Set width and height in the return object
        if (isset($transform['width'])) {
            $r['width'] = $transform['width'];
        }

        if (isset($transform['height'])) {
            $r['height'] = $transform['height'];
        }

        // set format
        if (isset($transform['format'])) {
            $r['format'] = $transform['format'];
        }

        // Set quality 
        if (!isset($transformerParams['quality']) && !isset($r['quality'])) {
            if (isset($r['format'])) {
                $r['quality'] = $this->getQualityFromExtension($r['format'], $transform);
            } else {
                $ext = null;

                if ($image instanceof Asset) {
                    $ext = $image->getExtension();
                }

                if (\is_string($image)) {
                    $pathParts = pathinfo($image);
                    $ext = $pathParts['extension'];
                }

                $r['quality'] = $this->getQualityFromExtension($ext, $transform);
            }
        }

        unset($transform['jpegQuality'], $transform['pngCompressionLevel'], $transform['webpQuality']);
        
        // Deal with resize mode, called crop mode/crop (cm/c) in Imagekit
        if (!isset($r['cropMode']) && !isset($r['crop']) && !isset($transformerParams['cropMode']) && !isset($transformerParams['crop'])) {
            if (isset($transform['mode'])) {
                $mode = $transform['mode'];

                switch ($mode) {
                    case 'fit':
                        $r['crop'] = $config->allowUpscale ? 'at_max_enlarge' : 'at_max';
                        break;
                    case 'stretch':
                        $r['crop'] = 'force';
                        break;
                    case 'croponly':
                        $r['cropMode'] = 'extract';
                        break;
                    case 'letterbox':
                        $r['cropMode'] = 'pad_resize';
                        $letterboxDef = $config->getSetting('letterbox', $transform);
                        $r['background'] = $this->getLetterboxColor($letterboxDef);
                        unset($transform['letterbox']);
                        break;
                    default:
                        $r['crop'] = 'maintain_ratio';
                        break;
                }

                unset($transform['mode']);
            } else {
                if (isset($r['width'], $r['height'])) {
                    $r['crop'] = 'maintain_ratio';
                } else {
                    $r['crop'] = $config->allowUpscale ? 'at_max_enlarge' : 'at_max';
                }
            }
        }
        
        // If fit is crop, and crop isn't specified, use position as focal point.
        if (isset($r['crop']) && $r['crop'] === 'maintain_ratio' && (!isset($transformerParams['focus']) && !isset($r['focus']))) {
            $position = $config->getSetting('position', $transform);
            $r['focus'] = $this->translatePosition($position);
            unset($transform['position']);
        }
        
        // Note: Padding is not supported by imagekit, removed. 
        
        // Add any explicitly set params
        foreach ($transformerParams as $key => $val) {
            $r[$key] = $val;
        }
        
        return $r;
    }

    /**
     * Gets the quality setting based on the extension.
     *
     * @param string $ext
     * @param array|null $transform
     *
     * @return string
     */
    private function getQualityFromExtension(string $ext, array $transform = null): string
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        switch ($ext) {
            case 'png':
                $pngCompression = $config->getSetting('pngCompressionLevel', $transform);
                return max(100 - ($pngCompression * 10), 1);
            case 'webp':
                return $config->getSetting('webpQuality', $transform);
            case 'avif':
                return $config->getSetting('avifQuality', $transform);
            case 'jxl':
                return $config->getSetting('jxlQuality', $transform);
        }

        return $config->getSetting('jpegQuality', $transform);
    }
    
    /**
     * Translate letterbox params to correct format.
     * 
     * ImageKit uses a weird RGBA veriant where the last two digits should be
     * the opacity between 00 and 99.
     */
    private function getLetterboxColor($letterboxDef): string
    {
        $color = $letterboxDef['color'];
        $opacity = $letterboxDef['opacity'];

        $color = str_replace('#', '', $color);

        if (\strlen($color) === 6) {
            $opacity = round($opacity * 99);
            return $color.(strlen($opacity) === 1 ? '0' : '').$opacity;
        }

        if (\strlen($color) === 3) {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
            $opacity = round($opacity * 99);
            return $color.(strlen($opacity) === 1 ? '0' : '').$opacity;
        }

        if (\strlen($color) === 8) { // assume color already is in the correct format. 
            return $color;
        }

        return 'ffffff00';
    }

    private function translatePosition(string $position): string
    {
        $r = [];
        
        [$left, $top] = explode(' ', $position);
        
        $left = (float)$left;
        $top = (float)$top;
        
        if ($top < 30) {
            $r[] = 'top';
        } elseif ($top > 70) {
            $r[] = 'bottom';
        }
        
        if ($left < 30) {
            $r[] = 'left';
        } elseif ($left > 70) {
            $r[] = 'right';
        } 
        
        if (count($r) > 0) {
            return implode('_', $r);
        }
        
        return 'center';
    }
}
