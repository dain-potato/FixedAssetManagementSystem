# FixedAssetManagementSystem
for Capstone 

**Before you can start**

# **Installation** 

before you start Coding Laravel, make sure that your local machine has PHP and Composer installed. In addition, we recommend installing Node and NPM.
## Links are here
[PHP](https://www.php.net/)

[composer](https://getcomposer.org/)

[node](https://nodejs.org/en)


# Run commands #

Installing the dependencies	

    composer install

configuration for environment
```bash
    php artisan key:generate
```
**Migrate from the Database**

- Before this Make sure to check the DB connection found in .env

```bash
php artisan migrate
```
Run Application
```bash
php artisan serve
```
Run vite to watch for any update from the Frontend
  ```bash
npm run dev
```
