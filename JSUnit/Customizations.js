var TYPO3 = TYPO3 || {};
TYPO3.ExitOnFinishReporter = function() {};
TYPO3.ExitOnFinishReporter.prototype.reportRunnerResults = function() {
	if (typeof window.quit == 'function') {
		quit();
	}
};