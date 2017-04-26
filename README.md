# terminal42/contao-bynder

This bundle integrates Bynder Asset Management directly into your Contao 4.4+
back end. Because as of today, Contao can only work with images that have been
uploaded to the filesystem (no filesystem abstraction yet), it works by adding
a new tab next to the regular file picker tab where you can choose your images
of your Bynder Asset Management account. Once you selected one or more images,
the bundle downloads the selected files in a folder you configured.

Still, this approach has many advantages as you can manage your files in one
central location and use it across multiple content management systems or even
multiple Contao setups.

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
    targetDir: 'bynder_assets' # The target dir the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path
```

