Statistics (module for Omeka S)
===============================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Statistics] is a module for [Omeka S] that computes statistics about resources:
items, item sets, media, properties, users, etc.

Since version 3.4.12, this module has been split into two modules: [Analytics],
for web analytics (page views, downloads, referrers, languages, user agents, etc.),
similar to tools like [Matomo] (open source), [Google Analytics] (proprietary,
no privacy); and [Statistics], this module, for internal statistics about
resources (counts by date, site, value, etc.).


Installation
------------

### Module

See general end user documentation for [installing a module].

The module [Common] must be installed first.

To get statistics, the module [Advanced Search] is required in order to do
advanced queries, in particular to get results by period.

The module uses an external library, so use the release zip to install it, or
use and init the source.

If you were using Statistics before version 3.4.12, install the module
[Analytics] first, then upgrade Statistics. The existing tables (`hit` and `stat`)
will be automatically reused by Analytics. If you do not need web analytics, for
example if you use another tool such as [Matomo], you can uninstall the module
Analytics afterwards.

* From the zip

Download the last release [Statistics.zip] from the list of releases, and
uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `Statistics`, go to the root module, and run:

```sh
composer install --no-dev
```


TODO
----

- [ ] Fixme: stats by value is wrong when a property has duplicate values for a resource (so fix it or warn about deduplicating values regularly (module BulkEdit)).
- [x] Fix and finalize statistics for public side and shortcodes.
- [ ] Statistics for api.
- [ ] Move some options to site settings.
- [x] Move all statistics methods from Stat and Hit models to Statistic Helper?
- [ ] Improve stats by item sets and move to helper Statistic.
- [ ] Enlarge item sets to any resource (item for media, periods, item set tree).
- [ ] Add tests.
- [ ] Include the right sidebar search form (the one used in site resources and pages) in by-value statistics.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Daniel Berthereau, 2014-2025 (see [Daniel-KM] on GitLab)


[Statistics]: https://gitlab.com/Daniel-KM/Omeka-S-module-Statistics
[Omeka S]: https://omeka.org/s
[Analytics]: https://gitlab.com/Daniel-KM/Omeka-S-module-Analytics
[Matomo]: https://matomo.org
[Google Analytics]: https://www.google.com/analytics
[web loggers]: https://en.wikipedia.org/wiki/List_of_web_analytics_software
[LibreOffice]: https://www.documentfoundation.org
[Statistics for Omeka Classic]: https://gitlab.com/Daniel-KM/Omeka-plugin-Stats
[Statistics.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-Statistics/-/releases
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[Advanced Search]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedSearch
[Shortcode]: https://gitlab.com/Daniel-KM/Omeka-S-module-Shortcode
[Archive Repertory]: https://gitlab.com/Daniel-KM/Omeka-S-module-ArchiveRepertory
[Access]: https://gitlab.com/Daniel-KM/Omeka-S-module-Access
[Derivative Media]: https://gitlab.com/Daniel-KM/Omeka-S-module-DerivativeMedia
[installing a module]: https://omeka.org/s/docs/user-manual/modules/
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Statistics/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: https://github.com/sandywalker/webui-popover/blob/master/LICENSE.txt
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
