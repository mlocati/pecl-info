module.exports = {
    productionSourceMap: false,
    lintOnSave: true,
    outputDir: 'docs',
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
        }
    },
    chainWebpack: config => {
        config
            .plugin('html')
            .tap(args => {
                args[0].title = 'PECL Info'
                return args
            })
    }
}
