const Encore = require('@symfony/webpack-encore');

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')

    // add the CSS entry point
    .addStyleEntry('styles', './assets/styles/style.css')

    // add the JavaScript entry point
    .addEntry('app', './assets/scripts/style.js')

    // enable Single Runtime Chunk
    .enableSingleRuntimeChunk()

    // cleans output before build
    .cleanupOutputBeforeBuild()

    // enable Source Maps for development environment
    .enableSourceMaps(!Encore.isProduction());

module.exports = Encore.getWebpackConfig();
