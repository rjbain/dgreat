//Install GULP in theme folder /web/themes/contrib/myusf
//npm init (Create the package.json file)
//npm install gulp -g gulp-sass gulp-cssmin gulp-concat gulp-imagemin gulp-smushit gulp-uglify --save-dev
//$ gulp (To run gulp once)
//$ gulp watch (To continuously watch so changes are automatically compiled) 

var sassFiles = 'sass/src/*.scss',
    cssFiles = 'css',
    cssSiteFile = 'site.css',
    imageFiles = 'images/src/*',
    imageMinFiles = 'images/site/'
    jsFiles = 'scripts/src/*.js',
    jsMinFiles = 'scripts/site/';
    jsSiteFile = 'site.js';

var gulp = require('gulp');
var sass = require('gulp-sass');
var cssmin = require('gulp-cssmin');
var concat = require('gulp-concat');
var imagemin = require('gulp-imagemin');
var smushit = require('gulp-smushit');
var uglify = require('gulp-uglify');

gulp.task('sass', function() {
    gulp.src(sassFiles)
        .pipe(sass().on('error', sass.logError))        
        .pipe(concat(cssSiteFile))
        .pipe(cssmin())
        .pipe(gulp.dest(cssFiles));
});

gulp.task('js', function(){
    return gulp.src(jsFiles)
        .pipe(concat(jsSiteFile))
        .pipe(gulp.dest(jsMinFiles))
        .pipe(uglify())
        .pipe(gulp.dest(jsMinFiles));
});

gulp.task('imagemin', function() {
    gulp.src(imageFiles)
        .pipe(imagemin())
        .pipe(gulp.dest(imageMinFiles))
});

gulp.task('smushit', function () {
    gulp.src(imageFiles)
        .pipe(smushit())
        .pipe(gulp.dest(imageMinFiles));
});

gulp.task('default',['sass','imagemin','smushit','js']);

gulp.task('watch', function() {
    gulp.watch(sassFiles, ['sass','js','imagemin','smushit'])
});