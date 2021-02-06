## ConnecTA
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

This repository contains code for backend including api service and frontend for web service. 
We are now using Symfony as our backend framework, and Vue as our frontend framework.

## Installation
### Environment
PHP >= 8.0, MySQL >= 8.0, Redis, Composer, NPM, Bower
### Configuration
Copy and configure all parameters form .env.dist correctly in .env
### Development
1. Install software environment.
1. Install dependencies using `composer install` and `npm install` in the root directory.
2. Install dependencies in public/assets directory using `bower install`. 
3. Migrate the database using `bin/console doctrine:schema:update --force`.
4. For Linux/maxOS, run `dev.sh`; For Windows, run `dev.bat`.
6. Now the website is available locally.
### Production
1. Run prod.sh, it will automatically install all the dependencies and migrate the database.

[comment]: <> (## Thank)

[comment]: <> (### BrowserStack)

[comment]: <> (<a href="https://browserstack.com"><img src="https://bstacksupport.zendesk.com/attachments/token/Ygvb0OdLftxe7bMxq5JHzEhQh/?name=browserstack-logo-600x315.png" width="200"/></a>)

[comment]: <> (We are using BrowserStack to test frontend compatibity on all major devices.)

[comment]: <> (## Contribution)

[comment]: <> (For all the things related to development&#40;e.g. issues, releasing schedules&#41;, please visit https://dev.nfls.io.)
