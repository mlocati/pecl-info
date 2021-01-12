const CopyWebpackPlugin = require('copy-webpack-plugin')

module.exports = {
    productionSourceMap: false,
    lintOnSave: true,
    outputDir: 'docs',
    publicPath: '.',
    configureWebpack: {
        resolve: {
            alias: {
                '@': __dirname + '/web'
            }
        },
        entry: {
            app: './web/main.ts'
        },
        performance: {
            maxEntrypointSize: 1048576,
            maxAssetSize: 1048576
        },
        plugins: [
            new CopyWebpackPlugin([
                { from: __dirname + '/.nojekyll', to: __dirname + '/docs/' }
            ])
        ]
    },
    chainWebpack: config => {
        config
            .plugin('html')
            .tap(args => {
                args[0].template =  __dirname + '/web/static/index.html'
                return args
            })
    }
}
