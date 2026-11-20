<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImagekitTransformer;

use CraftCms\Cms\Plugin\PluginSettings;

class Settings extends PluginSettings
{
    public string $publicKey = '';
    public string $privateKey = '';
    public bool $signUrls = false;
    public int $signedUrlsExpireSeconds = 31_536_000;
    public bool $stripUrlQueryString = true;
    public string $defaultProfile = '';
    public array $profiles = [];
    public array $defaultParams = [];
    public bool $purgeEnabled = true;
}
