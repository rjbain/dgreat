const { src, dest, series, parallel, watch } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const concat = require('gulp-concat');
const imagemin = require('gulp-imagemin');
const imageminPngquant = require('imagemin-pngquant');
const terser = require('gulp-terser');

// Paths
const paths = {
  styles: {
    src: 'sass/src/*.scss',
    dest: 'css',
    output: 'site.css'
  },
  scripts: {
    src: 'scripts/src/*.js',
    dest: 'scripts/site',
    output: 'site.js'
  },
  images: {
    src: 'images/src/*.{png,jpg,jpeg}',
    dest: 'images/site'
  }
};

// Compile SCSS
function compileSass() {
  return src(paths.styles.src)
    .pipe(sass().on('error', sass.logError))
    .pipe(concat(paths.styles.output))
    .pipe(cleanCSS())
    .pipe(dest(paths.styles.dest));
}

// Combine & Minify JS
function compileJS() {
  return src(paths.scripts.src)
    .pipe(concat(paths.scripts.output))
    .pipe(terser())
    .pipe(dest(paths.scripts.dest));
}

// Optimize Images
function optimizeImages() {
  return src(paths.images.src)
    .pipe(imagemin([
      imageminPngquant({
        quality: [0.6, 0.8],
        speed: 1
      })
    ]))
    .pipe(dest(paths.images.dest));
}

// Watch Task
function watchFiles() {
  watch(paths.styles.src, compileSass);
  watch(paths.scripts.src, compileJS);
  watch(paths.images.src, optimizeImages);
}

exports.sass = compileSass;
exports.js = compileJS;
exports.images = optimizeImages;
exports.watch = watchFiles;
exports.default = parallel(compileSass, compileJS, optimizeImages);