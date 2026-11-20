# Upgrading to ImageKit transformer 3 (Craft 6)

This guide lists what changed in version 3 of the ImageKit transformer for Imager X, and what you need to update in your project and custom code. It applies when upgrading from version 2 (Craft 5 and Imager X 5 or 6).

Templates that transform with ImageKit through Imager X (`craft.imagerx.transformImage()` with the `imagekit` transformer) aren't affected. Projects need to move and convert the config file. The class changes only affect custom code that uses the plugin's classes directly, for example to purge images.

## Requirements

- Craft CMS 6.0.0 or later
- Imager X 7.0.0-beta.2 or later, Pro edition
- PHP 8.5 or later
- The plugin doesn't need the Yii 2 adapter (`craftcms/yii2-adapter`).

## Upgrade steps

1. Upgrade your project to Craft 6, and Imager X to version 7.
2. Update the plugin: `composer require spacecatninja/imager-x-imagekit-transformer:^3.0.0`.
3. Move your config file to `config/craft/imager-x-imagekit-transformer.php`, and convert it (see below).
4. Review the breaking changes below, and update your code.

## Breaking changes

### The config file

Craft 6 reads plugin config files from `config/craft/`, as plain Laravel config. Move `config/imager-x-imagekit-transformer.php` to `config/craft/imager-x-imagekit-transformer.php`. If it uses the multi-environment `'*'` format, return a flat array instead, and use `env()` instead of `craft\helpers\App::env()`. The settings themselves are unchanged.

Before:

```php
use craft\helpers\App;

return [
    '*' => [
        'publicKey' => App::env('IMAGEKIT_PUBLIC_KEY'),
        'privateKey' => App::env('IMAGEKIT_PRIVATE_KEY'),
        'defaultProfile' => 'default',
        'profiles' => [
            'default' => [
                'urlEndpoint' => 'https://ik.imagekit.io/myurlendpoint',
            ],
        ],
    ],
];
```

After:

```php
return [
    'publicKey' => env('IMAGEKIT_PUBLIC_KEY', ''),
    'privateKey' => env('IMAGEKIT_PRIVATE_KEY', ''),
    'defaultProfile' => 'default',
    'profiles' => [
        'default' => [
            'urlEndpoint' => 'https://ik.imagekit.io/myurlendpoint',
        ],
    ],
];
```

Config files are loaded before Laravel knows the environment, so use `env()` for per-environment values, not `app()->environment()`. For example: `'urlEndpoint' => env('APP_ENV') === 'production' ? 'https://ik.imagekit.io/production' : 'https://ik.imagekit.io/staging'`.

### The cloud source path

With a profile's `useCloudSourcePath` setting on, the path of an asset in the URL endpoint starts with the asset's path in cloud storage. On Craft 5 that was the filesystem's subfolder and the volume's subpath. Craft 6 volumes use Laravel filesystem disks, so it's now the `root` of the volume's disk, followed by the volume's subpath. If your S3 or GCS disk's `root` matches the subfolder your Craft 5 filesystem had, the URLs are unchanged.

### Transform URLs

Some transform URLs change:

- A transform with mode `fit`, or with only a `width` or `height`, that sets `allowUpscale` itself now gets `c-at_max` or `c-at_max_enlarge` from it. Version 2 always used the config's `allowUpscale`.
- With modes `crop` and `stretch` and `allowUpscale` off, the size of an asset is now clamped to the source. ImageKit always enlarges images in these modes.

### Namespace and classes

The plugin moved from `spacecatninja\imagekittransformer` to the `SpaceCatNinja\ImagekitTransformer` namespace, with Laravel-style folders.

| Before | After |
| --- | --- |
| `spacecatninja\imagekittransformer\ImagekitTransformer` | `SpaceCatNinja\ImagekitTransformer\ImagekitTransformer` |
| `spacecatninja\imagekittransformer\models\Settings` | `SpaceCatNinja\ImagekitTransformer\Settings` |
| `spacecatninja\imagekittransformer\models\ImagekitProfile` | `SpaceCatNinja\ImagekitTransformer\Data\ImagekitProfile` |
| `spacecatninja\imagekittransformer\models\ImagekitTransformedImageModel` | `SpaceCatNinja\ImagekitTransformer\Data\ImagekitTransformedImageModel` |
| `spacecatninja\imagekittransformer\helpers\ImagekitHelpers` | `SpaceCatNinja\ImagekitTransformer\Support\ImagekitHelpers` |
| `spacecatninja\imagekittransformer\transformers\Imagekit` | `SpaceCatNinja\ImagekitTransformer\Transformers\Imagekit` |

`ImagekitTransformedImageModel` extends Imager X 7's `SpaceCatNinja\ImagerX\Data\BaseTransformedImageModel`, and `ImagekitProfile` extends Craft 6's `CraftCms\Cms\Component\Component`. The `Imagekit` transformer no longer extends `craft\base\Component`, and the classes throw Imager X 7's `SpaceCatNinja\ImagerX\Exceptions\ImagerException`.

### Settings and purging

`ImagekitTransformer::$plugin` was removed. Read the plugin settings with `app(SpaceCatNinja\ImagekitTransformer\Settings::class)` instead of `ImagekitTransformer::$plugin->getSettings()`.

`ImagekitHelpers::purgeAsset()` is unchanged. It now posts to ImageKit's purge API with Laravel's HTTP client instead of the ImageKit SDK, and the new `ImagekitHelpers::purgeUrl()` purges a single URL. The purge event handler is now the `SpaceCatNinja\ImagekitTransformer\Listeners\PurgeFromImagekit` listener, registered for Craft 6's `AssetReplaced` event.

Before:

```php
use spacecatninja\imagekittransformer\helpers\ImagekitHelpers;

ImagekitHelpers::purgeAsset($asset);
```

After:

```php
use SpaceCatNinja\ImagekitTransformer\Support\ImagekitHelpers;

ImagekitHelpers::purgeAsset($asset);
```
