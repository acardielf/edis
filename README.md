# Edis for e-distribucion

A PHP package (based on https://github.com/trocotronic/edistribucion) for use in PHP projects to access data from e-distribucion private area.  

Developed and distributed under GPL-3.0 License and exclusively for academic purposes

### Installing in your project:

`composer require edistribucion\edistribucion`

### Installation and test in isolated environment using Docker:

1. Run `cp .env.dist .env` and edit with your details
2. Run `docker compose up -d` for running in de-attached mode
3. Run `docker exec -it edis bash` for enter in bash mode inside the container
4. You should be able to run `php test/index.php` 

### Usage 

```php 
use Edistribucion\EdisClient;

$edis = new EdisClient(<YOUR_USERNAME>,<YOUR_PASSWORD>);
$edis->login();
```
After succeed login, you should be able to execute predefined actions
```php 
$edis->get_cups();
$edis->get_cups_info($homeCups);
$edis->get_meter($homeCups);
$edis->get_cups_detail($homeCups);
$edis->get_cups_status($homeCups);
$edis->reconnect_ICP($homeCups);
$edis->get_list_cups($homeCups);
$edis->get_list_cycles($homeCups);
$edis->get_meas($homeCups, "24/01/2022 - 20/02/2022", "*****");
$edis->get_measure($homeCups);
$edis->get_maximeter($homeCups);
```

### TO-DO 
There're many things to do... maybe never will be get all of them. For have a public list:
* Documentation: It's necessary have a full list of actions that package can do
* Refactor: This code it's susceptible to refactor splitting in smaller classes or using PHP-DI
* Testing: This code hasn't been developed using DDD paradigm and have not any automatic testing.
 

### About this project
This project is part of the main bachelor's degree final project for TI Engineering for the Universitat Overta de Catalunya UOC. Therefore, this project is exclusively for academic purposes.

### Thanks
Thanks to @trocotronic, @duhow, @polhenarejos and rest for their contribution and for open me the way

### DISCLAIMER
Please note: this package is released for use "AS IS" **without any warranties** of any kind, including, but not limited to their installation, use, or performance. We disclaim any and all warranties, either express or implied, including but not limited to any warranty of non-infringement, merchantability, and/ or fitness for a particular purpose. We do not warrant that the technology will meet your requirements, that the operation thereof will be uninterrupted or error-free, or that any errors will be corrected.

**Any use of these scripts and tools is at your own risk**. There is no guarantee that they have been through thorough testing in a comparable environment, and we are not responsible for any damage or data loss incurred with their use.

You are responsible for reviewing and testing any scripts you run thoroughly before use in any non-testing environment.
