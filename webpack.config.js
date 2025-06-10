const path = require('path')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = {
   entry: './assets_dev/admin.ts',
   module: {
      rules: [
         {
            test: /\.tsx?$/,
            use: 'ts-loader',
            exclude: /node_modules/,
         },
      ],
   },
   resolve: {
      extensions: ['.tsx', '.ts', '.js'],
   },
   output: {
      filename: 'admin.js',
      path: path.resolve(__dirname, 'cavWP', 'assets'),
   },
   optimization: {
      minimize: true,
      minimizer: [new TerserPlugin()],
   },
   mode: 'production',
}
