<?php
/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2023 André Elvan
 */

namespace spacecatninja\imagekittransformer\models;

use craft\base\Model;

class Settings extends Model
{
    public string $publicKey = '';
    public string $privateKey = '';
    public bool $signUrls = false;
    public int $signedUrlsExpireSeconds = 31_536_000;
    public bool $stripUrlQueryString = true;
    public string $defaultProfile = '';
    public array $profiles = [];
    public array $defaultParams = [];
}
