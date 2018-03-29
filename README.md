[![PHP: 7.0 7.1](https://img.shields.io/badge/PHP-7.0%207.1-green.svg)](http://www.php.net)
[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
![stability-testing](https://img.shields.io/badge/stability-testing-yellow.svg)

# Entropy
Analytical data repository for Cultural Heritage laboratories

## Status

BETA / TESTING

Although Entropy is considered relatively stable (beta), the authors cannot be held accountable for any damage that is caused by installing or operating this software. THERE IS NO WARRANTY WHATSOEVER FOR THE PROGRAM.

## Planned milestones

### [v1.0](https://github.com/KIKIRPA/Entropy/releases/tag/v1.0) (15 March 2018)
- Single user (more users are possible, but no front-end)
- Twodimensional data (spectra)
- File conversion from/to common file formats for Raman spectroscopy
- Flexible file-based data storage (database-less)
- Mass import of data (metadata supplied as comma-separated-values file)
- Terms of usage / licensing on measurement, library and repository levels

### M1.1
- Support linked datasets
- Viewer for static images
- Improved import module

### M2
- Multi-user (user and permission management)
- Single file import and editing existing data
- Explore the possibilities for storing/converting/displaying large datasets (internally stored)
- Support for mapping/imaging data

### M3
- API
- Implement database storage (ElasticSearch, MongoDB...)
- ...

## Installation

Check the [Wiki page](https://github.com/KIKIRPA/Entropy/wiki/Installation-instructions) for general installation instructions on a clean install of Ubuntu Linux Server 16.04 LTS. Do not blindly follow these instructions on production machines.

## Credits

Development: Wim Fremout

With the support of:

[![Royal Institute for Cultural Heritage (KIK-IRPA)](https://github.com/KIKIRPA/Entropy/blob/master/public_html/img/kikirpalogo.png "KIK-IRPA")](http://www.kikirpa.be)
[![IPERION CH](https://github.com/KIKIRPA/Entropy/blob/master/public_html/img/iperionlogo.png "IPERION-CH")](http://www.iperionch.eu)

- [Royal Institute for Cultural Heritage (KIK-IRPA)](http://www.kikirpa.be), Brussels
- [IPERION CH](http://www.iperionch.eu) (project funded by the European Commission, H2020-INFRAIA-2014-2015)

Incorporates code from:
- jQuery (Apache v2.0) by the jQuery Foundation, jquery.com
- DataTables (GPL v2 / BSD) by Allan Jardine, www.datatables.net
- Dygraphs (MIT license) by Dan Vanderkam, dygraphs.com
- Flickity (GPL v3 license) by Metafizzy LLC, flickity.metafizzy.co
- Notify Bar (MIT license) by Dmitri Smirnov, www.whoop.ee
- Freecons (Free) by Jiri Silha
