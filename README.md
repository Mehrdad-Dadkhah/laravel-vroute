# Laravel-VRoute

[![Software License](https://img.shields.io/badge/license-GPL-brightgreen.svg?style=flat-square)](LICENSE) [![Packagist Version](https://img.shields.io/packagist/v/Mehrdad-Dadkhah/laravel-vroute.svg?style=flat-square)](https://packagist.org/packages/Mehrdad-Dadkhah/LaravelVRoute)
  

PHP library for laravel to add automated/conventional routes with versioning.
 

This package help developers and teams to have versioning URI base on project structure, and no need to write new line of code for new route.


This package is configable for example you can change template of controller names and ....
 

If you have ```index``` method in version 1 of ```PostController``` and you call ```/api/v1/post``` so router call index method of version 1 immidately, but if call ```/api/v2/post```  **what happen?**

you have 3 choice:

1. move all codes from version 1 to version 2!

  
2. add new route for ```/api/v2/post``` that point to version 1!

3. in your application you use different version of api base on active version of it! you have know multiple active api version, you should support all, you should remember or check every day! where which version is in use and .....

So to fix this problem/need I develop this package. VRoute for ```/api/v2/post``` check version 2 of PostController and if it has not this method automatically switch to one version.

Maybe you ask this process is heavy and may make performance problem, yes but I add good caching system to it and we process only one time.

## Benefit case and samples

1. **Simplification and less code**
**Berfore** use VRoute you may have sth like this:
    ```PHP
    Route::namespace ('Admin')->prefix('admin')->group(function () {
    
        Route::namespace ('V1')->prefix('v1')->group(function () {
    
            Route::post('users/login', 'UserAPIController@login');
            Route::post('users/refresh-token', 'UserAPIController@refreshToken');
            Route::post('users/forget-password', 'UserAPIController@forgetPassword');
            Route::post('users/register', 'UserAPIController@register');
    
            Route::resource('faqs', 'FaqAPIController');
            Route::get('settings/check-version', 'SettingAPIController@checkVersion');
    
            Route::group(['middleware' => 'auth:api'], function () {
                Route::post('users/logout', 'UserAPIController@logout');
                Route::get('users/show', 'UserAPIController@show');
                Route::put('users/notification-toggle', 'UserAPIController@toggleStatusNotification');
    
                Route::get('users/channels', 'UserTaggingAPIController@channels');
    
                /** ---------------------- ACL middleware  ----------------------------------- */
                Route::middleware(['acl'])->group(function () {
                    Route::get('scores/totals', 'ScoreAPIController@totals');
                    Route::resource('posts', 'PostAPIController');
                });
            });
        });
    });
    
    Route::namespace ('Client')->prefix('client')->group(function () {
    
        Route::namespace ('V1')->prefix('v1')->group(function () {
    
            Route::post('users/login', 'UserAPIController@login');
            Route::post('users/refresh-token', 'UserAPIController@refreshToken');
            Route::post('users/forget-password', 'UserAPIController@forgetPassword');
            Route::post('users/register', 'UserAPIController@register');
    
            Route::resource('faqs', 'FaqAPIController');
            Route::get('settings/check-version', 'SettingAPIController@checkVersion');
    
            Route::group(['middleware' => 'auth:api'], function () {
                Route::post('users/logout', 'UserAPIController@logout');
                Route::get('users/show', 'UserAPIController@show');
                Route::put('users/notification-toggle', 'UserAPIController@toggleStatusNotification');
    
                Route::get('users/channels', 'UserTaggingAPIController@channels');
    
                /** ---------------------- ACL middleware  ----------------------------------- */
                Route::middleware(['acl'])->group(function () {
                    Route::get('scores/totals', 'ScoreAPIController@totals');
                    Route::resource('posts', 'PostAPIController');
                });
            });
        });
    
        Route::namespace ('V2')->prefix('v2')->group(function () {
    
            Route::post('users/login', 'UserAPIController@login');
            Route::post('users/refresh-token', 'UserAPIController@refreshToken');
            Route::post('users/forget-password', 'UserAPIController@forgetPassword');
            Route::post('users/register', 'UserAPIController@register');
    
            Route::resource('faqs', 'FaqAPIController');
            Route::get('settings/check-version', 'SettingAPIController@checkVersion');
    
            Route::group(['middleware' => 'auth:api'], function () {
    
                Route::get('users/channels', 'UserTaggingAPIController@channels');
    
                /** ---------------------- ACL middleware  ----------------------------------- */
                Route::middleware(['acl'])->group(function () {
                    Route::resource('posts', 'PostAPIController');
                });
            });
        });
    
    });
    ```
    
    And **after** use VRoute it will be:
    ```PHP
    VRoute::setAvailableSubDirs([
        'admin',
        'client',
    ]);
    
    VRoute::setMiddleware('UserAPIController', 'POSTLogout', ['auth:api']);
    VRoute::setMiddleware('UserAPIController', 'GETShow', ['auth:api']);
    VRoute::setMiddleware('UserTaggingAPIController', 'PUTNotificationToggle', ['auth:api']);
    VRoute::setMiddleware('UserAPIController', 'GETChannels', ['auth:api']);
    VRoute::setMiddleware('ScoreAPIController', 'GETTotals', ['auth:api', 'acl']);
    VRoute::setMiddleware('PostAPIController', null, ['auth:api', 'acl']); //work for all versions
    
    VRoute::run(request());
    ```

2. **Procedural unity**
3. **Team conventions**
4. **More redable code in controller**
    **Before** use Vroute:
    ```PHP
    class UserAPIController extends AppBaseController
    {
        //some codes .....
        public function notification(): JsonResponse
        {
            //some codes
        }
    }
    ```
    
    **After** use VRoute:
    ```PHP
    class UserAPIController extends AppBaseController
    {
        //some codes .....
        public function PUTNotification(): JsonResponse
        {
            //some codes
        }
    }
    ```
## System requirements

Tested with >=7.1, following binaries need to be installed

  
## Installation

```
composer require mehrdad-dadkhah/laravel-vroute
```

## Usage

In route folder, put this code in your route file (for example api.php)

```PHP
use App\VRoute;

VRoute::run(request());
```

**That's it!**

If you have sub-directory in you project, for example admin/client and .... should make them available for VRoute:

```PHP
VRoute::setAvailableSubDirs([
'admin',
'client',
]);
```

## How to set middleware?

You can set middleware in 8 layer or way!

1. Set for controller in all version and directory

2. Set for controller in specific directory (for example only for ```PostController``` in ```admin``` sub-dir)
  
3. Set for controller in specific version (for example only for ```PostController``` in ```v2```)

4. Set for controller in specific directory and version (for example only for ```PostController``` in ```admin``` sub-dir and only version 1)

5. Set for controller and specific action(method) (for example only for ```PostController``` and ```GETProfile``` action)

6. Set for controller and specific action(method) in specific version (for example only for ```PostController``` and ```GETProfile``` action in ```V2```)

7. Set for controller and specific action(method) in specific directory (for example only for ```PostController``` and ```GETProfile``` action in ```admin``` sub-dir)

8. Set for controller and specific action(method) in specific directory and version (for example only for ```PostController``` and ```GETProfile``` action in ```V2``` and ```admin```)

**What about you set all 8 kind of middleware for one controller?**

From top to bottom, if match the route, all middlewares will be merged.

For example we set:

1.  ```web1``` middleware for ```PostController``` (without specify directory and version)

2.  ```web2``` middleware for ```PostController``` for ```admin``` directory (without specify version)

3.  ```web3``` middleware for ```PostController``` for ```v1``` version (without specify directory)

4.  ```web4``` middleware for ```PostController``` for ```admin``` directory and ```v1``` version

Now we call ```/api/admin/v1/post```, so all of the middlewares (```web1```,```web2```,```web3```,```web4```) will be fire

But If call ```/api/admin/post```, only ```web1``` and ```web2`` will be fire


## Now, how to set it (middleware)?

First case (set for controller in all version and directory):

```PHP
VRoute::setMiddleware('PostAPIController', null, ['web1']);
```

Second case (set for controller in specific directory):

```PHP
VRoute::setMiddleware('PostAPIController', null, ['web2'], 'admin');
```

Third case (set for controller in specific version):

```PHP
VRoute::setMiddleware('PostAPIController', null, ['web3'], null, 'v1');
```
Fourth case (set for controller in specific directory and version):
```PHP
VRoute::setMiddleware('PostAPIController', null, ['web4'], 'admin', 'v1');
```

**Second argument is action/method name**

All of this can repeat for action case when set second param. for example:

```PHP
VRoute::setMiddleware('PostAPIController', 'GETDetail', ['web1']);
```

and so on ...

## Use VRoute methods and write your own specific route

You can get all data you need from VRoute functions:

```PHP
VRoute::getControllerPath(request());

VRoute::getControllerName(request());

VRoute::getAction(request());

VRoute::getNamspace(request());

VRoute::getSubDirs(request());
```

For example:

```PHP
$request = request();

Route::get('/post', VRoute::getNamspace($request).'@'.VRoute::getAction($request))->name('post-index');
```
 

## Conventions

1. All controller methods(actions) name should be with ```{HTTPMETHOD}{ActionName}``` template for example if you want have profile method that get user profile data, should name it ```GETProfile```. this convention add to force action about it's method, force methods to response to only one type of http call method and make controller more readable.

  
## To Do
  
 - Cache found routes
 - Add clean cache command
 - Add VRoute:cache command to iterate on controllers and cache all routes
 - Response cache feature

## License

laravel-vroute is licensed under the [GPLv3 License](http://opensource.org/licenses/GPL).