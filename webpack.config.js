const { Encore } = require('@terminal42/contao-build-tools');

module.exports = Encore()
    .setOutputPath('public/')
    .setPublicPath('/bundles/terminal42contaobynder')
    .enableVueLoader()
    .addEntry('app', './view/js/app.js')
    .getWebpackConfig()
;