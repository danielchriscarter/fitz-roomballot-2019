# Fitz JCR Housing Ballot System
[![Maintainability](https://api.codeclimate.com/v1/badges/d9809cce796fad2fc9cf/maintainability)](https://codeclimate.com/github/CHTJonas/roomballot/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/d9809cce796fad2fc9cf/test_coverage)](https://codeclimate.com/github/CHTJonas/roomballot/test_coverage)
This is the code repository for Fitzwilliam College JCR's online Room & Housing Ballot System. It is currently a work in progress and we do not have an estimated project release date.

The app itself is written in PHP and is designed to run on the SRCF's web server, but in theory it should be easily deployed elsewhere too. The authentication uses the `$_SERVER['REMOTE_USER']` PHP variable and is designed to work seamlessly with the Raven single sign-on service at the University of Cambridge, however it should be easily adaptable to any other kind of authentication backend.

Roomballot was written by:
* Charlie Jonas (JCR Webmaster 2016)
* Tom Benn (JCR Webmaster 2017)

## Installation
1. Run `git clone https://github.com/CHTJonas/roomballot.git` in a terminal.
2. Edit `.htaccess.example` and `app/Environment.php.example` as necessary.
3. Remove the `.example` part.

## Contributing
You are cordially invited to contribute to this project! Please see [Code of Conduct](https://github.com/CHTJonas/roomballot/blob/master/CODE_OF_CONDUCT.md) and [Contributing](https://github.com/CHTJonas/roomballot/blob/master/CONTRIBUTING.md) for more information.

## License & Legal
The Fitzwilliam College JCR Room Balloting System is released as open source/libre software under Version 3 of the GNU General Public License. See the LICENSE file for the full details, but to summarise:
* The software is released “as is” without warranty of any kind. The entire risk as to the quality and performance of the software is with you. Should the software prove defective, you assume the cost of all necessary servicing, repair or correction.
* In no event will we be liable to you for damages arising out of the use or inability to use the program (eg. loss of data, data being rendered inaccurate, failure of the program to operate with any other programs et cetera).

Fitzwilliam College Junior Combination Room is a separate organisation and legal entity from both Fitzwilliam College and the University of Cambridge, none of which hold legal copyright over this software.
