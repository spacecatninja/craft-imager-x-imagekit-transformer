# ImageKit transformer for Imager X

A plugin for using [ImageKit](https://imagekit.io/) as a transformer in Imager X.   
Also, an example of [how to make a custom transformer for Imager X](https://imager-x.spacecat.ninja/extending.html#transformers).

## Requirements

This plugin requires Craft CMS 6.0.0 or later, [Imager X 7.0.0](https://github.com/spacecatninja/craft-imager-x/) or later
(the Pro edition, like all custom transformers), PHP 8.5 or later, and an account at [ImageKit](https://imagekit.io/).

Version 2.x of this plugin supports Craft CMS 5 and Imager X 5 and 6. See [UPGRADE.md](UPGRADE.md) when moving to Craft 6.
 
## Usage

Install and configure this transformer as described below. Then, in your [Imager X config](https://imager-x.spacecat.ninja/configuration.html), 
`config/craft/imager-x.php`, set the transformer to `imagekit`, ie:

```
'transformer' => 'imagekit',
``` 

Transforms are now by default transformed with ImageKit, test your configuration with a 
simple transform like this:

```
{% set transform = craft.imagerx.transformImage(asset, { width: 600 }) %}
<img src="{{ transform.url }}" width="600">
<p>URL is: {{ transform.url }}</p>
``` 

If this doesn't work, make sure you've configured a `defaultProfile`, have a profile with the correct name, and 
that ImageKit is configured correctly.

### Cave-ats, shortcomings, and tips

This transformer only supports a subset of what Imager X can do when using the default `craft` transformer. 
All the basic transform parameters are supported, with the following exceptions:

- Focal points are translated to focus anchor points (ie `top`, `left`, `center`, etc).  
– Watermarks are not translated automatically from Imager syntax to ImageKit's, but you can still add watermarks by manually passing them through the `transformerParams` object (see below).   
- No effects are currently converted automatically.    
- ImageKit always enlarges images in its `maintain_ratio` and `force` crop strategies, which the `crop` and `stretch`
  modes use. When `allowUpscale` is off, the transformer clamps the size of assets to the source instead: crops keep
  their aspect ratio, and `stretch` clamps each side on its own, like Imager does. Images transformed from a path or
  URL aren't clamped.
- ImageKit refuses to make images larger than its resolution limit, 25 megapixels by default, and returns a 400 with an
  `ELIMIT` error.

To pass additional options directly to ImageKit, you can use the `transformerParams` transform parameter. Example:

```
{% set transforms = craft.imagerx.transformImage(asset, 
    [{width: 400}, {width: 600}, {width: 800}], 
    { ratio: 2/1, transformerParams: { rotation: 45 } }
) %}
```   

Refer to the [ImageKit PHP SDK](https://github.com/imagekit-developer/imagekit-php) for parameters to use, and the
[ImageKit documentation](https://docs.imagekit.io/) for more information.


## Installation

To install the plugin, follow these instructions:

1. Install with composer via `composer require spacecatninja/imager-x-imagekit-transformer` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings > Plugins, or from the command line via `php artisan craft:plugin:install imager-x-imagekit-transformer`.


## Configuration

You can configure the transformer by creating a file called `imager-x-imagekit-transformer.php` in your 
`config/craft/` folder, and override settings as needed. It's a plain Laravel config file, so use `env()` for 
the keys and other environment-specific values.

```php
<?php

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

### publicKey [string]
Default: `''`  
Public key for your ImageKit account (Developer Options > API Keys, in ImageKit).

### privateKey [string]
Default: `''`  
Private key for your ImageKit account (Developer Options > API Keys, in ImageKit).

### signUrls [bool]
Default: `false`  
Toggle this on to generate signed URLs. This is required if "Restrict unsigned image URLs" in 
Settings > Images > Security is turned on.

### signedUrlsExpireSeconds [int]
Default: `31536000` (one year)   
Duration for signed URLs. 

### stripUrlQueryString [bool]
Default: `true`  
By default all query strings are stripped from full URLs passed to ImageKit, to improve
caching. If you use full URLs that rely on query strings, you can toggle this off to make
them work. 

### profiles [array]
Default: `[]`  
Profiles are usually a one-to-one mapping to the URL endpoints you've created in ImageKit. But due to the
nature of ImageKit URL endpoints, where you can mix different storages per endpoint, it could also make sense 
to create several profiles for one endpoint for different use-cases (ie, one to use as a web proxy and the other
for relative paths).
You set the default profile to use using the `defaultProfile` config setting, and can override it at the 
template level by setting `profile` in your `transformerConfig`.

Example profile:

```
'profiles' => [
    'default' => [
        'urlEndpoint' => 'https://ik.imagekit.io/myurlendpoint',
        'isWebProxy' => false,
        'useCloudSourcePath' => true,
    ],
    'proxy' => [
        'urlEndpoint' => 'https://ik.imagekit.io/myotherurlendpoint',
        'isWebProxy' => true
    ]
],
```

Each profile has the following settings:

#### urlEndpoint [string]
Default: `''`       
This is the URL endpoint you created in ImageKit.

#### isWebProxy [bool]
Default: `false`       
Indicates if the URL endpoint uses a web proxy or not. If enabled, full URLs will be passed to ImageKit, 
instead of the relative path to the asset.

#### defaultParams [array]
Default: `[]`       
Default params to be passed to every transform.

#### useCloudSourcePath [bool]
Default: `false`       
If enabled, Imager will prepend the volume's path in its cloud storage to the asset path, before adding it to the 
ImageKit URL. The path is the `root` of the volume's filesystem disk, followed by the volume's subpath. This makes it 
possible to have one ImageKit source pulling images from many Craft volumes when they are for instance on the same S3 
bucket, but in different folders. This only applies to cloud disks (for instance S3 and GCS), not local disks.

#### addPath [string|array]
Default: `[]`       
Prepends a path to the asset's path. Can be useful if you have 
several volumes that you want to serve with one ImageKit source. If this setting is an array, the key 
should be the volume handle, and the value the path to add.

### defaultProfile [string]
Default: `''`  
Sets the default profile to use (see `profiles`). You can override the profile at the template level by setting it through `transformerConfig`. Example:

```
{% set transforms = craft.imagerx.transformImage(asset, 
    [{width: 800}, {width: 2000}], 
    null,
    { transformerConfig: { profile: 'myotherprofile' } }
) %}
```

### defaultParams [array]
Default: `[]`  
Default params to pass to all transforms.

### purgeEnabled [bool]
Default: `true`  
Toggles automatic purging on/off. When it's on, and `publicKey` and `privateKey` are set, an asset is purged from
every profile that isn't a web proxy when its file is replaced in Craft.


Price, license and support
---
The plugin is released under the MIT license. It requires Imager X, which is a commercial 
plugin [available in the Craft plugin store](https://plugins.craftcms.com/imager-x). If you 
need help, or found a bug, please post an issue in this repo, or in Imager X' repo (preferably). 
