var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('src/Resources/public')
    .setPublicPath('/bundles/terminal42contaobynder')
    .addEntry('app', './view/js/app.js')
    .disableSingleRuntimeChunk()
    .enableVueLoader()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableSassLoader()
    .setManifestKeyPrefix('bundles/terminal42contaobynder')
;

module.exports = Encore.getWebpackConfig();
