Crowdin Translation Support
===========================

Crowdin (https://crowdin.net/) is an online localization management platform.
The tools in this folder allow to use it for Flow packages.

Configuration
-------------

You need to have the crowdin-cli tool installed, see https://crowdin.net/page/cli-tool.

Now create a JSON file in your project root, e.g. named ``crowdin.json``::

    {
        "acme-foo": {
            "name": "Acme Foo",
            "path": "Packages/Application/Acme.Foo",
            "apiKey": "<project api key>"
        },
        "acme-bar": {
            "name": "Acme Bar",
            "path": "Packages/Application/Acme.Bar",
            "apiKey": "<project api key>"
        }
    }

.. note:: You should never commit this to Git, as it contains your secret keys.
   You can also use the option of specifying the key(s) via an environment variable.

Now you can run::

    php Build/BuildEssentials/Crowdin/Setup.php `pwd`/crowdin.json

This will create ``crowdin.yaml`` in every project configured in ``crowdin.json``.

.. note:: You should exclude ``crowdin.yaml`` from Git, so it cannot be committed by
   accident (as it contains your secret keys) or use the option of specifying the
   key(s) via an environment variable.

Using an environment variable for the API key
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To use an environment variable for the API key, you must specify the name of the
variable in the ``crowdin.json``file, **wrapped in percent signs**. Then, when
actually calling the scripts to setup, upload or download, define the variable
as usual.

Bundling projects
^^^^^^^^^^^^^^^^^

Instead of using a single Crowdin project per package, you can also use bundling to have all
XLIFF files (sources and translations) be handled in a single Crowdin project. This allows
for better overview, uses a single translation memory and glossary and thus makes the life
of translators easier.

To use bundling, set up ``crowdin.json`` like above but include a special ``__bundle`` entry::

    {
        "__bundle": {
            "projectIdentifier": "<project identifier>",
            "apiKey": "<project api key>"
        },
        "acme-foo": {
            …
        }
    }

The individual project entries can still have ``apiKey`` entries, they will be ignored.
Keeping them allows to reuse the same ``crowdin.json`` file for bundled and non-bundled
use (should that ever be needed).

Then call the setup script like this::

    php Build/BuildEssentials/Crowdin/Setup.php `pwd`/crowdin.json --bundle

Usage
-----

Now you can run::

    php Build/BuildEssentials/Crowdin/Upload.php `pwd`/crowdin.json

This will upload the source XLIFF files to Crowdin. Using::

    php Build/BuildEssentials/Crowdin/Upload.php `pwd`/crowdin.json --translations

sources and existing translations will be uploaded (synchronized) in one go.

Running this will download the translations from Crowdin::

    php Build/BuildEssentials/Crowdin/Download.php `pwd`/crowdin.json

This updates the XLIFF files with translations from Crowdin; review and commit as
you like.

All of the above commands can be called with ``--bundle`` to use project bundling::

    php Build/BuildEssentials/Crowdin/Upload.php `pwd`/crowdin.json --bundle
    php Build/BuildEssentials/Crowdin/Upload.php `pwd`/crowdin.json --bundle --translations
    php Build/BuildEssentials/Crowdin/Download.php `pwd`/crowdin.json --bundle

To remove all generated ``crowdin.yaml`` files again use::

    php Build/BuildEssentials/Crowdin/Teardown.php `pwd`/crowdin.json

