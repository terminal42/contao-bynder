# terminal42/contao-bynder

This bundle integrates Bynder Asset Management directly into your Contao 4.4+
back end. Because as of today, Contao can only work with images that have been
uploaded to the filesystem (no filesystem abstraction yet), it works by adding
a new tab next to the regular file picker tab where you can choose your images
of your Bynder Asset Management account. You can search and filter your Bynder
assets, download and then select them right from the file tree widget.

## Why would I need this, if it still downloads all the files to the system?

This approach still has one big advantage which is that you can manage your files
in one central location and use it across multiple content management systems or
even multiple Contao setups.

## Roadmap / Ideas

**Warning:** This is an early - not even alpha - version of the bundle. We use it
in a bigger project and it's still under active development and stuff might change
over time. If you're interested in having a stable version of it very soon, consider
getting in touch with us so we can find a solution for this.

Because of this, there is still a lot of things we could do that is not done yet:

- [ ] Support filters other than `select`
- [ ] Find a way how one can control which filters are shown and which ones are not
- [ ] Support the picker wizard in Contao, not only the FileTree widget
- [ ] More?

## Installation

1) Install the bundle

```
$ composer require terminal42/contao-bynder
```

2) Enable the bundle (you can skip this if you're using the Contao Managed Edition!)

Enable the bundle by adding the following line in the app/AppKernel.php` file of your project:

```php
<?php

// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Terminal42\ContaoBynder\Terminal42ContaoBynderBundle(),
        );

        // ...
    }
}
```

3) Configure the bundle

Edit your `config.yml` file and add the necessary configuration parameters as
follows:

```yaml
# You can get the consumer key and token and the respective tokens as described on https://developer-docs.bynder.com/API/Authorization-and-authentication/#create-a-consumer
terminal42_contao_bynder:
    consumerKey: 'foobar'
    consumerSecret: 'foobar'
    token: 'foobar'
    tokenSecret: 'foobar'
    targetDir: 'bynder_assets' # The target dir the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path (In that case it would be default store the images in /files/bynder_assets)
    derivativeOptions: # See documentation of Bynder settings
        w: 3000
        h: 3000
        crop: false

```

4) Configure your Bynder Account

Bynder can handle huge file assets that do not even make sense in a web environment
such as a 750 MB TIFF image. However, Bynder knows the concept of "derivatives".
Derivatives are variations of the original image. They allow you to create e.g.
a 3000 x 3000 pixel JPEG file from your 750 MB TIFF file. You can ask Bynder to
create them every time a user uploads a file or you can create so called on-the-fly
derivatives. 
On-the-fly derivatives even allow any third party application to override the desired
parameters. Go and have a look at the "Supported parameters" section in the [Bynder
knowledge base][1] to see how you can override these.
The `terminal42_contao_bynder.derivativeOptions` setting takes a key value array
which it will append when fetching a derivative to do exactly that.
We recommend to use the following setting:

```yaml
derivativeOptions: 
    w: 3000
    h: 3000
    crop: false
```

This will create an image of decent size and it will not crop it but keep the original
proportions which is usually what you want to have. You **absolutely do not need**
to pass this configuration. You can also create a derivative only for this integration
and name it e.g. "contao_bynder_integration" and configure the settings already there.

Note that derivatives can only be created for public assets.
This is also the reason why **all images that you want to include need to be public!**

[1]: https://help.bynder.com/Modules/Asset-Bank/Modify-public-derivatives-on-the-fly.htm
