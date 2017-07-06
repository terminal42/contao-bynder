'use strict';

const gulp = require('gulp');
const vueify = require('vueify');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
const eslint = require('gulp-eslint');
const browserify = require('browserify');
const babelify = require('babelify');
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');
const sass = require('gulp-sass');

// ESLint
gulp.task('lint', function () {
    return gulp.src(['view/js/app.js', '!node_modules/**'])
        .pipe(eslint())
        .pipe(eslint.format())
        .pipe(eslint.failAfterError());
});

// Compile JS
gulp.task('compile-js', function () {
    return browserify({entries: 'view/js/app.js'})
        .transform(vueify)
        .transform(babelify)
        .bundle()
        .pipe(source('view/js/app.js'))
        .pipe(buffer())
        .pipe(uglify())
        .pipe(rename('app.min.js'))
        .pipe(gulp.dest('src/Resources/public'));
});

// Compile SASS
gulp.task('compile-sass', function () {
    return gulp.src('view/css/styles.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(rename('styles.min.css'))
        .pipe(gulp.dest('src/Resources/public'));
});

// Compile
gulp.task('compile', ['compile-js', 'compile-sass']);

// Watch task
gulp.task('watch', function () {
    gulp.watch(['view/css/**'], ['compile-sass']);
    gulp.watch(['view/js/**'], ['compile-js']);
});

// Default task
gulp.task('default', ['lint', 'compile']);
