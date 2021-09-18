var gulp = require('gulp');
var sass = require('gulp-sass')(require('sass'));

var cssInput = './src/sass/**/*.scss';
var cssOutput = './assets/css/';

var sassOptions = {
	errLogToConsole: true,
	outputStyle: 'expanded',
	indentType: 'tab',
	indentWidth: 1,
	charset: false
};

var jsInput = './src/jsx/**/*.jsx';
var jsOutput = './assets/js/';

gulp.task( 'sass', function () {
	return gulp
	// Find all `.scss` files from the `stylesheets/` folder
	.src(cssInput)
	// Run Sass on those files
	.pipe(sass(sassOptions).on('error', sass.logError))
	// Write the resulting CSS in the output folder
	.pipe(gulp.dest(cssOutput));
});

// Watchers
gulp.task( 'watch', () => {
	gulp.watch(
		[ cssInput ],
		gulp.series([ 'sass' ] )
	);
});

// Default
gulp.task( 'default', gulp.series( 'sass', 'watch' ) );
