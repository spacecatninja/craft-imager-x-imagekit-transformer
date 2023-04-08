<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2023 André Elvan
 */

namespace spacecatninja\imagekittransformer\helpers;

use craft\elements\Asset;
use craft\fs\Local;
use craft\helpers\App;
use craft\helpers\FileHelper;
use spacecatninja\imagekittransformer\ImagekitTransformer;
use spacecatninja\imagekittransformer\models\ImagekitProfile;
use spacecatninja\imagekittransformer\models\Settings;
use spacecatninja\imagerx\exceptions\ImagerException;
use yii\base\InvalidConfigException;

class ImagekitHelpers
{

    public static function getProfile(string $name): ?ImagekitProfile
    {
        $settings = ImagekitTransformer::$plugin->getSettings();
        
        if ($settings && isset($settings->profiles[$name])) {
            return new ImagekitProfile($settings->profiles[$name]);
        }
        
        return null;
    }

    public static function getFilePath(Asset|string $image, ImagekitProfile $profile, Settings $config): string
    {
        if (\is_string($image)) { // if $image is a string, just pass it to builder, we have to assume the user knows what he's doing (sry) :)
            return $image;
        }
        
        if ($profile->isWebProxy) {
            return $settings->stripUrlQueryString ? UrlHelper::stripQueryString($image->url) : $image->url;
        }
            
        try {
            $volume = $image->getVolume();
            $fs = $image->getVolume()->getFs();
        } catch (InvalidConfigException $invalidConfigException) {
            \Craft::error($invalidConfigException->getMessage(), __METHOD__);
            throw new ImagerException($invalidConfigException->getMessage(), $invalidConfigException->getCode(), $invalidConfigException);
        }

        if (($profile->useCloudSourcePath) && (property_exists($fs, 'subfolder') && $fs->subfolder !== null) && $fs::class !== Local::class) {
            $path = implode('/', [App::parseEnv($fs->subfolder), $image->getPath()]);
        } else {
            $path = $image->getPath();
        }
        
        if (!empty($config->addPath)) {
            if (\is_string($config->addPath) && $config->addPath !== '') {
                $path = implode('/', [$config->addPath, $path]);
            } elseif (is_array($config->addPath)) {
                if (isset($config->addPath[$volume->handle])) {
                    $path = implode('/', [$config->addPath[$volume->handle], $path]);
                }
            }
        }
        
        $path = FileHelper::normalizePath($path);

        //always use forward slashes for imgix
        $path = str_replace('\\', '/', $path);

        return $path;
    }

}
