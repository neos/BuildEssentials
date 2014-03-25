Crowdin Translation Support
===========================

Crowdin (https://crowdin.net/) is an online localization management platform.
The tools in this folder allow to use it for Flow packages.

You need to have the crowdin-cli binary installed, see https://crowdin.net/page/cli-tool.

Now create a JSON file in your project root, e.g. named ``crowdin.json``::

	{
		"typo3-form": {
			"name": "Acme Foo",
			"path": "Packages/Application/Acem.Foo",
			"apiKey": "<project api key>"
		},
		"typo3-media": {
			"name": "Acme Bar",
			"path": "Packages/Application/Acme.Bar",
			"apiKey": "<project api key>"
		}
	}

.. note:: You should never committ this to Git, as it contains your secret keys.

Now you can run::

	php Build/BuildEssentials/Setup.php `pwd`/crowdin.json

This will create ``crowdin.yaml`` in every project configured in ``crowdin.json``.

.. note:: You should exclude ``crowdin.yaml`` from Git, so it cannot be committed by
	accident (as it contains your secret keys)

Now you can run::

	php Build/BuildEssentials/Upload.php `pwd`/crowdin.json

This will upload the source XLIFF files and existing translation to Crowdin. Using::

	php Build/BuildEssentials/Upload.php `pwd`/crowdin.json --translations

existing translations will be uploaded (synchronized) as well.

Running this will download the translations from Crowdin::

	php Build/BuildEssentials/Download.php `pwd`/crowdin.json

The updates the XLIFF files with translations from crowdin, review and commit as
you like.

To remove all generated ``crowdin.yaml`` files again use::

	php Build/BuildEssentials/Teardown.php `pwd`/crowdin.json

