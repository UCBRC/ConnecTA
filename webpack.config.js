// webpack.config.js
var Encore = require('@symfony/webpack-encore');
var path = require('path');

function resolve (dir) {
    return path.join(__dirname, dir)
}

Encore
    .setOutputPath('public/vue/')
    .setPublicPath('/vue')
    .addEntry('vue', './web/main.js')
    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableVueLoader(function(options){
        // options.loaders = {
        //     i18n:
        // }
    })
    // create hashed filenames (e.g. app.abc123.css)
    .enableVersioning()
    .addLoader({
        test: /\.(json5?|ya?ml)$/, // target json, json5, yaml and yml files
        type: 'javascript/auto',
        loader: '@intlify/vue-i18n-loader',
        include: [ // Use `Rule.include` to specify the files of locale messages to be pre-compiled
            path.resolve(__dirname, 'web/translation/frontend')
        ]
    })
    .addLoader({
        test: /\.js$/,
        loader: 'babel-loader',
        include: [
            resolve('node_modules/webpack-dev-server/client'),
            resolve('node_modules/markdown-palettes/src'),
            resolve('node_modules/ali-oss/dist'),
            resolve('node_modules/ali-oss/lib'),
            resolve('node_modules/ali-oss/shims'),
            resolve('node_modules/vue-material/src'),
            resolve('node_modules/vue-material/src'),
            resolve('node_modules/vue-preview')
        ]
    })
;
module.exports = Encore.getWebpackConfig();

