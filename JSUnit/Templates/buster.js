var config = module.exports;

config['SomeGroupName'] = {
	rootPath: '../../',
	environment: 'browser',
	tests: ['Packages/Application/**/Tests/JavaScript/**/*.js'],
	libs: [
		'Packages/Application/My.Acme/Resources/Public/JavaScript/require.js'
	],
	resources: [
		'Web/_Resources/Static/Packages/**/*.js'
	],
	sources: [
		'Packages/Application/My.Acme/Tests/JavaScript/Unit.js'
	]
}