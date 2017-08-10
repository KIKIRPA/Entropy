[![PHP: 7.0 7.1](https://img.shields.io/badge/PHP-7.0%207.1-green.svg)](http://www.php.net)
[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![stability-experimental](https://img.shields.io/badge/stability-experimental-orange.svg)](https://github.com/emersion/stability-badges#experimental)

# Entropy
Analytical data repository for Cultural Heritage laboratories

## Status

EXPERIMENTAL - UNSTABLE
Entropy is under heavy development and may or may not function. Expect it to fail, destroy all your irreplaceable data, vaporize your server, kill your dog and start a world-wide nuclear war. THERE IS NO WARRANTY WHATSOEVER FOR THE PROGRAM. You have been warned!

## Planned milestones

### M1
- Single user (more users are possible, but no front-end)
- Twodimensional data (spectra)
- File conversion from/to common file formats for Raman spectroscopy
- Flexible file-based data storage (database-less)
- Mass import of data (metadata supplied as comma-separated-values file)

### M2
- Multi-user (user and permission management)
- Single file import and editing existing data
- Support for very large (linked) datasets
- Support for mapping/imaging data
- Implementation of a CSS framework

### M2
- API
- Implement database storage (ElasticSearch, MongoDB...)
- ...

## Installation

Check the [Wiki page](https://github.com/KIKIRPA/Entropy/wiki/Installation-instructions) for general installation instructions on a clean install of Ubuntu Linux Server 16.04 LTS. Do not blindly follow these instructions on production machines.

## Credits

Development: Wim Fremout

With the support of:
- [Royal Institute for Cultural Heritage (KIK-IRPA)](http://www.kikirpa.be), Brussels
- [IPERION CH](http://www.iperionch.eu) (project funded by the European Commission, H2020-INFRAIA-2014-2015)

[![Royal Institute for Cultural Heritage (KIK-IRPA)](https://github.com/KIKIRPA/Entropy/blob/master/public_html/img/kikirpa.png "KIK-IRPA")](http://www.kikirpa.be)
[![IPERION CH](https://github.com/KIKIRPA/Entropy/blob/master/public_html/img/iperionklein.png "IPERION-CH")](http://www.iperionch.eu)

Incorporates code from:
- Dygraphs (MIT license) by Dan Vanderkam, dygraphs.com
- Notify Bar (MIT license) by Dmitri Smirnov, www.whoop.ee
- DataTables (GPL v2 / BSD) by Allan Jardine, www.datatables.net
- jQuery (Apache v2.0) by the jQuery Foundation, jquery.com
- Freecons (Free) by Jiri Silha
