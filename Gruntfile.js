/**
 * Build scripts.
 */

module.exports = function(grunt) {

	// load most all grunt tasks
	require('load-grunt-tasks')(grunt);

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				compress: {
					global_defs: {
						"EO_SCRIPT_DEBUG": false
					},
					dead_code: true
				},
				banner: '/*! <%= pkg.name %> <%= pkg.version %> */\n'
			},
			build: {
				files: [{
					expand: true, // Enable dynamic expansion.
					src: ['js/*.js', '!js/*.min.js'], // Actual pattern(s) to match.
					ext: '.min.js', // Dest filepaths will have this extension.
				}, ]
			}
		},
		jshint: {
			options: {
				reporter: require('jshint-stylish'),
				globals: {
					"EO_SCRIPT_DEBUG": false,
				},
				'-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
			},
			all: ['js/*.js', '!js/*.min.js']
		},

		// Remove the build directory files
		clean: {
			main: ['build/**']
		},

		// Copy the plugin into the build directory
		copy: {
			main: {
				src: [
					'**',
					'!node_modules/**',
					'!readme.md/**',
					'!composer.json',
					'!package-lock.json',
					'!build/**',
					'!license.txt',
					'!readme.md',
					'!wp-assets/**',
					'!deploy/**',
					'!.git/**',
					'!Gruntfile.js',
					'!package.json',
          			'!gitcreds.json',
          			'!.gitcreds',
          			'!.transifexrc',
					'!.gitignore',
					'!.gitmodules',
					'!**/*.sublime-workspace',
					'!**/*.sublime-project',
					'!.wordpress-org/**',
					'!.github/**',
					'!webpack.config.js',
					'!.eslintrc.json',
					'!.distignore',
					'!**/*~',
				],
				dest: 'build/'
			}
		},

		// Generate git readme from readme.txt
		wp_readme_to_markdown: {
			convert: {
				files: {
					'readme.md': 'readme.txt'
				},
			},
		},

		// Make a zipfile.
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: 'deploy/<%= pkg.name %>-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'build/',
				src: ['**/*'],
				dest: '/<%= pkg.name %>'
			}
		},

		// bump version numbers
		replace: {
			Version: {
				src: [
					'readme.txt',
					'readme.md',
					'<%= pkg.name %>.php'
				],
				overwrite: true,
				replacements: [
					{
						from: /Stable tag:.*$/m,
						to: "Stable tag: <%= pkg.version %>"
					},
					{
						from: /Version:.*$/m,
						to: "Version:           <%= pkg.version %>"
					},
					{
						from: /public static \$version = \'.*.'/m,
						to: "public static $version = '<%= pkg.version %>'"
					},
					{
						from: /public \$version      = \'.*.'/m,
						to: "public $version      = '<%= pkg.version %>'"
					}
				]
			}
		}

	});

	// Default task(s).
	grunt.registerTask('default', ['jshint', 'uglify']);

	grunt.registerTask('test', ['jshint']);

	grunt.registerTask('docs', ['wp_readme_to_markdown']);

	grunt.registerTask('build', ['replace', 'newer:uglify', 'wp_readme_to_markdown']);

	grunt.registerTask('zip', ['clean', 'copy', 'build', 'compress']);

};
