hris-payroll-backend

## Setting up the Job Scheduling Queue

1. Ensure Redis is running and is set up to be found by Laravel through the .env file.
2. __[Optional for Debian-based systems]__ Supervisor needs to be installed to enable background daemon processes: https://laravel.com/docs/5.6/queues#supervisor-configuration
3. Create a Laravel Queue process by running
```bash
$ php artisan queue:work --env=development
```
4. Ensure that this process is not killed, unless it's managed by Supervisor.
