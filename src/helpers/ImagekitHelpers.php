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
            $fs = $volume->getFs();
        } catch (InvalidConfigException $invalidConfigException) {
            \Craft::error($invalidConfigException->getMessage(), __METHOD__);
            throw new ImagerException($invalidConfigException->getMessage(), $invalidConfigException->getCode(), $invalidConfigException);
        }

        if ($profile->useCloudSourcePath && property_exists($fs, 'subfolder') && $fs::class !== Local::class) {
            $path = implode('/', [App::parseEnv($fs->subfolder), App::parseEnv($volume->getSubpath()), $image->getPath()]);
        } else {
            $path = $image->getPath();
        }

        if (!empty($profile->addPath)) {
            if (\is_string($profile->addPath) && $profile->addPath !== '') {
                $path = implode('/', [$profile->addPath, $path]);
            } elseif (is_array($profile->addPath)) {
                if (isset($profile->addPath[$volume->handle])) {
                    $path = implode('/', [$profile->addPath[$volume->handle], $path]);
                }
            }
        }

        $path = FileHelper::normalizePath($path);

        //always use forward slashes for imgix
        $path = str_replace('\\', '/', $path);

        return $path;
    }

    public static function purgeAsset(Asset $asset): void
    {
        /** @var Settings $settings */
        $settings = ImagekitTransformer::getInstance()?->getSettings();

        if (!$settings) {
            return;
        }

        $publicKey = $settings->publicKey;
        $privateKey = $settings->privateKey;

        if (empty($publicKey) || empty($privateKey)) {
            return;
        }

        $profiles = $settings->profiles;

        foreach ($profiles as $profile) {
            $profileModel = new ImagekitProfile($profile);

            if ($profileModel->isWebProxy) {
                continue;
            }

            $imageKit = new \ImageKit\ImageKit(
                $settings->publicKey,
                $settings->privateKey,
                $profileModel->urlEndpoint
            );

            try {
                $path = self::getFilePath($asset, $profileModel, $settings);
            } catch (ImagerException $imagerException) {
                \Craft::error('An error occured when trying to get file path to purge: '.$imagerException->getMessage(), __METHOD__);
            }

            $response = $imageKit->purgeCache(rtrim($profileModel->urlEndpoint, '/').'/'.$path);

            if ($response && $response->error && $response->error->message) {
                \Craft::error('An error occured when trying to purge asset for ImageKit: '.print_r([
                        'path' => $path,
                        'response' => $response
                    ], true), __METHOD__);
            }
        }
    }
}
