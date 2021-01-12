module.exports = {
    lintOnSave: true,
    outputDir: __dirname + '/docs',
    configureWebpack: {
        resolve: {
            alias: {
                '@': __dirname + '/web'
            }
        },
        entry: {
            app: './web/main.ts'
        }
    }
}
