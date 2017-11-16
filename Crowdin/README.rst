Crowdin Translation Support
===========================

Crowdin (https://crowdin.net/) is an online localization management platform.
The tools in this folder allow to use it for Flow packages.

Configuration
-------------

You need to have the crowdin-cli tool installed, see https://support.crowdin.com/cli-tool/.

Now create a JSON file in your project root, e.g. named ``crowdin.json``::

    {
        "project": {
            "branch": "",
            "identifier": "neos",
            "apiKey": "%CROWDIN_API_KEY%"
        },

        "items": {
            "neos": {
                "path": "Packages/Neos/*"
            },

            "framework": {
                "path": "Packages/Framework/*"
            },

            "addons": {
                "path": "Packages/Application/Neos.*"
            }
        }
    }

.. note:: You should exclude ``crowdin.json`` from Git, so it cannot be committed by
   accident (as it contains your secret keys) or use the option of specifying the
   key(s) via an environment variable.

Using an environment variable for the API key
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To use an environment variable for the API key, you must specify the name of the
variable in the ``crowdin.json``file, **wrapped in percent signs**. Then, when
actually calling the scripts to setup, upload or download, define the variable
as usual.

Usage
-----

Now you can run::

    php Build/BuildEssentials/Crowdin/Setup.php `pwd`/crowdin.json

This will create ``crowdin.yaml`` based on the configuration in ``crowdin.json``.

.. note:: You should exclude ``crowdin.yaml`` from Git, so it cannot be committed by
   accident (as it contains your secret keys) or use the option of specifying the
   key(s) via an environment variable.

Then run::

    php Build/BuildEssentials/Crowdin/Upload.php `pwd`/crowdin.json

to upload the source XLIFF files to Crowdin. Using::

    php Build/BuildEssentials/Crowdin/Upload.php `pwd`/crowdin.json --translations

sources and existing translations will be uploaded (synchronized) in one go.

Running this will download the translations from Crowdin::

    php Build/BuildEssentials/Crowdin/Download.php `pwd`/crowdin.json

This updates the XLIFF files with translations from Crowdin; review and commit as
you like.

To remove the generated ``crowdin.yaml`` file again use::

    php Build/BuildEssentials/Crowdin/Teardown.php `pwd`/crowdin.json

