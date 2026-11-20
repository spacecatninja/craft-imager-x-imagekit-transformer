<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImagekitTransformer\Support;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SpaceCatNinja\ImagekitTransformer\Data\ImagekitProfile;
use SpaceCatNinja\ImagekitTransformer\Settings;
use SpaceCatNinja\ImagerX\Exceptions\ImagerException;

class ImagekitHelpers
{
    /**
     * @var string
     */
    public const PURGE_ENDPOINT = 'https://api.imagekit.io/v1/files/purge';

    public static function getProfile(string $name): ?ImagekitProfile
    {
        $settings = app(Settings::class);

        if (isset($settings->profiles[$name])) {
            return new ImagekitProfile($settings->profiles[$name]);
        }

        return null;
    }

    /**
     * @throws ImagerException
     */
    public static function getFilePath(Asset|string $image, ImagekitProfile $profile, Settings $config): string
    {
        if (\is_string($image)) { // if $image is a string, just pass it to builder, we have to assume the user knows what he's doing (sry) :)
            return $image;
        }

        if ($profile->isWebProxy) {
            $url = $image->getUrl() ?? '';

            return $config->stripUrlQueryString ? Url::stripQueryString($url) : $url;
        }

        try {
            $volume = $image->getVolume();
            $disk = $volume->sourceDisk();
        } catch (\Throwable $throwable) {
            Log::error($throwable->getMessage());
            throw new ImagerException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }

        if ($profile->useCloudSourcePath && !$disk instanceof LocalFilesystemAdapter) {
            // The path in the bucket: the disk's root, the volume's subpath, then the asset's path in the volume
            $path = $disk->path($image->getPath());
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

        // Always use forward slashes for ImageKit
        return File::normalizePath($path, '/');
    }

    /**
     * Purges an asset from ImageKit's cache, for every profile that isn't a web proxy.
     *
     * @see https://docs.imagekit.io/api-reference/media-api/purge-cache
     */
    public static function purgeAsset(Asset $asset): void
    {
        $settings = app(Settings::class);

        if (empty($settings->publicKey) || empty($settings->privateKey)) {
            return;
        }

        foreach ($settings->profiles as $profile) {
            $profileModel = new ImagekitProfile($profile);

            if ($profileModel->isWebProxy) {
                continue;
            }

            try {
                $path = self::getFilePath($asset, $profileModel, $settings);
            } catch (ImagerException $imagerException) {
                Log::error('An error occurred when trying to get file path to purge: ' . $imagerException->getMessage());
                continue;
            }

            self::purgeUrl(rtrim($profileModel->urlEndpoint, '/') . '/' . $path, $settings->privateKey);
        }
    }

    /**
     * Purges a URL from ImageKit's cache. Errors are logged, not thrown.
     */
    public static function purgeUrl(string $url, string $privateKey): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::error('An error occurred when trying to purge asset for ImageKit, the URL "' . $url . '" is not valid.');

            return;
        }

        try {
            $response = Http::withBasicAuth($privateKey, '')
                ->timeout(30)
                ->post(self::PURGE_ENDPOINT, ['url' => $url]);

            if ($response->status() < 200 || $response->status() > 300) {
                Log::error('An error occurred when trying to purge asset for ImageKit: ' . print_r([
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ], true));
            }
        } catch (\Throwable $throwable) {
            Log::error('An error occurred when trying to purge asset for ImageKit: ' . print_r([
                'url' => $url,
                'error' => $throwable->getMessage(),
            ], true));
        }
    }
}
