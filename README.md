<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


## Multitenancy with Laravel

Multi-tenancy in web applications refers to the architecture where a single instance of the application serves multiple customers, or 'tenants.' Each tenant's data and, sometimes, specific configurations are kept isolated from others. This setup is essential in SaaS (Software as a Service) platforms where multiple businesses or organizations might use the same application.

### ``` php artisan tenant:init``` to init the owner database.
### ``` php artisan tenant:create``` to create the tenant database.
### ``` php artisan tenant:migrate``` to migrate the tenant database.