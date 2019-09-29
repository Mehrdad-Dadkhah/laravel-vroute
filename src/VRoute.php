<?php

namespace MehrdadDadkhah\VRoute;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VRoute
{
    /** @var string[] */
    private static $uriParts = [];

    /** @var bool|nul; */
    private static $isAPI = null;

    /** @var string|null */
    private static $normalizedURI = null;

    /** @var string|null */
    private static $version = null;

    /** @var string|null */
    private static $versionInURI = false;

    /** @var string|null */
    private static $versionPrefix = null;

    /** @var string */
    private static $controllerPath = '';

    /** @var string */
    private static $controllerNamespace = '';

    /** @var string */
    private static $versionFolderName = '';

    /** @var string */
    private static $controllerNameTemplate = '';

    /** @var int */
    private static $controllerKey = 0;

    /** @var string */
    private static $namespace = '';

    /** @var string[] */
    private static $subDirs = [
        'admin',
        'client',
    ];

    /** @var string[] */
    private static $inURISubDirs = [];

    /** @var string[] */
    private static $middlewares = [];

    /**
     * init all needed configs
     *
     * @return void
     */
    private static function initConfigs()
    {
        if (self::$versionPrefix !== null) {
            return;
        }

        self::$versionPrefix          = config('vroute.version.prefix', 'v');
        self::$controllerPath         = config('vroute.controller.path', base_path('app/Http/Controllers/'));
        self::$controllerNamespace    = config('vroute.controller.namespace', 'App\Http\Controllers');
        self::$versionFolderName      = config('vroute.version.folderName', 'V');
        self::$controllerNameTemplate = config('vroute.controller.nameTemplate', '{Name}{API}Controller');
    }

    /**
     * get version from URI function
     *
     * @param Request $request
     * @return string
     */
    private static function getVersion(Request $request): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        $pattern = sprintf('/\/%s(.*?)\/./s', self::$versionPrefix);
        \preg_match($pattern, self::normalizeURI($request), $mathches);

        if (isset($mathches[1])) {
            self::$versionInURI   = true;
            return self::$version = $mathches[1];
        }

        return self::$version = '1';
    }

    /**
     * Reduce version number when not found method in called version in URI function
     *
     * @param Request $request
     * @return string
     */
    private static function reduceAPIVersion(Request $request): string
    {
        $basePath = self::$controllerPath;
        $basePath .= (self::isAPI($request) ? 'API/' : '');
        $basePath .= implode('/', self::getSubDirs($request));

        $versions = [];
        $dirs     = self::getSubDirectories($basePath);
        foreach ($dirs as $dir) {
            $exp = explode('/' . self::$versionFolderName, $dir);
            if (isset($exp[1]) && (float) $exp[1] < (float) self::$version) {
                $versions[] = $exp[1];
            }
        }

        if (empty($versions)) {
            throw new NotFoundHttpException(self::getNamspace($request) . '\\' . self::getControllerName($request) . ' with action NotFound ' . self::getAction($request));
        }

        sort($versions);

        return (string) (self::$version = last($versions));
    }

    /**
     * normalize requested URI function
     *
     * @param Request $request
     * @return string
     */
    private static function normalizeURI(Request $request): string
    {
        if (self::$normalizedURI !== null) {
            return self::$normalizedURI;
        }

        return self::$normalizedURI = strtolower($request->getRequestUri());
    }

    /**
     * Check requested URI is call an API or not?
     *
     * @param Request $request
     * @return boolean
     */
    protected static function isAPI(Request $request): bool
    {
        if (self::$isAPI !== null) {
            return self::$isAPI;
        }

        return self::$isAPI = (strpos(self::normalizeURI($request), 'api') !== false);
    }

    /**
     * parse and explode parts of URI function
     *
     * @param Request $request
     * @return string[]
     */
    protected static function parseURI(Request $request): array
    {
        if (!empty($uriParts)) {
            return self::$uriParts;
        }

        self::$uriParts = explode('/', self::normalizeURI($request));
        unset(self::$uriParts[0]);
        self::$uriParts = array_values(self::$uriParts);

        return self::$uriParts;
    }

    /**
     * get recursively sub subdirectories of specific dir function
     *
     * @param string $dir
     * @return array
     */
    private static function getSubDirectories(string $dir): array
    {
        $subDir      = [];
        $directories = array_filter(glob($dir . '/*'), 'is_dir');
        $subDir      = array_merge($subDir, $directories);

        foreach ($directories as $directory) {
            $subDir = array_merge($subDir, self::getSubDirectories($directory . '/*'));
        }

        return $subDir;
    }

    /**
     * sub-dirs setter function
     *
     * @param array $dirs
     * @return void
     */
    public static function setAvailableSubDirs(array $dirs)
    {
        self::$subDirs = $dirs;
    }

    /**
     * get sub-dirs in URI function
     *
     * @param Request $request
     * @return array
     */
    public static function getSubDirs(Request $request): array
    {
        if (!empty(self::$inURISubDirs)) {
            return self::$inURISubDirs;
        }

        $parts = self::parseURI($request);
        foreach ($parts as $uriPart) {
            if (in_array($uriPart, self::$subDirs)) {
                self::$inURISubDirs[] = self::camelize($uriPart);
            }
        }

        return self::$inURISubDirs;
    }

    /**
     * make strings/names camelize function
     *
     * @param string $str
     * @param string $delimeter
     * @return string
     */
    private static function camelize(string $str, string $delimeter = '_'): string
    {
        // split string by '-'
        $words = explode($delimeter, $str);
        $words = (count($words) <= 1) ? explode('-', $str) : $words;
        if (count($words) <= 1) {
            return ucfirst($str);
        }

        // make a strings first character uppercase
        $words = array_map('ucfirst', $words);

        // join array elements with '-'
        return self::camelize(implode('', $words), '-');
    }

    /**
     * get controller file path function
     *
     * @param Request $request
     * @return void
     */
    public static function getControllerPath(Request $request)
    {
        self::initConfigs();

        if (self::isAPI($request)) {
            $pathParts[] = 'API';
        }

        foreach (self::getSubDirs($request) as $dir) {
            $pathParts[] = $dir;
        }
        $pathParts[] = self::$versionFolderName . self::getVersion($request);

        self::$namespace = self::$controllerNamespace . '\\' . implode('\\', $pathParts);
        $path            = self::$controllerPath . implode('/', $pathParts);

        if (file_exists($path . '/' . self::getControllerName($request) . '.php')) {
            $obj = app(self::$namespace . '\\' . self::getControllerName($request));
            if (!method_exists($obj, self::getAction($request))) {
                self::reduceAPIVersion($request);
                return self::getControllerPath($request);
            }
        } else {
            self::reduceAPIVersion($request);
            return self::getControllerPath($request);
        }

        return $path;
    }

    /**
     * get controller namespace function
     *
     * @param Request $request
     * @return void
     */
    public static function getNamspace(Request $request)
    {
        if (self::$namespace !== '') {
            return self::$namespace;
        }

        self::getControllerPath($request);
        return self::$namespace;
    }

    /**
     * find and set controller index in URI pars array function
     *
     * @param Request $request
     * @return void
     */
    private static function initControllerKey(Request $request)
    {
        if (self::$controllerKey !== 0) {
            return;
        }

        self::$controllerKey = 1;

        if (self::isAPI($request)) {
            self::$controllerKey++;
        }

        self::$controllerKey += count(self::getSubDirs($request));
    }

    /**
     * get controller name base on template in confog function
     * Default is: {Name}{API}Controller
     *
     * @param Request $request
     * @return string
     */
    public static function getControllerName(Request $request): string
    {
        self::initConfigs();
        self::initControllerKey($request);

        $parts = self::parseURI($request);

        if (!isset($parts[self::$controllerKey])) {
            throw new NotFoundHttpException($request->getUri() . ' not found!');
        }

        $name = str_replace(
            '{Name}',
            self::camelize($parts[self::$controllerKey]),
            self::$controllerNameTemplate
        );

        $name = str_replace(
            '{API}',
            (self::isAPI($request) ? 'API' : ''),
            $name
        );

        return $name;
    }

    /**
     * get action name base on http request method
     *
     * @param Request $request
     * @return string
     */
    public static function getAction(Request $request): string
    {
        $parts = self::parseURI($request);
        self::initControllerKey($request);

        $action = 'index';
        if (isset($parts[self::$controllerKey + 1])) {
            $action = $request->method() . self::camelize($parts[self::$controllerKey + 1]);
        }

        return $action;
    }

    /**
     * middlewares setter function
     *
     * @param string $controllerName
     * @param string|null $method
     * @param array $middlewares
     * @param string|null $subDir
     * @param string|null $version
     * @return void
     */
    public static function setMiddleware(
        string $controllerName,
        ?string $method,
        array $middlewares,
        ?string $subDir = null,
        ?string $version = null
    ) {
        self::$middlewares[$controllerName]['middlewares'] = [];

        if ($method !== null && $subDir !== null && $version !== null) {
            self::$middlewares[$controllerName][$subDir][$version][$method]['middlewares'] = $middlewares;
        } elseif ($method !== null && $subDir !== null) {
            self::$middlewares[$controllerName][$subDir][$method]['middlewares'] = $middlewares;
        } elseif ($method !== null && $version !== null) {
            self::$middlewares[$controllerName][$version][$method]['middlewares'] = $middlewares;
        } elseif ($subDir !== null && $version !== null) {
            self::$middlewares[$controllerName][$subDir][$version]['middlewares'] = $middlewares;
        } elseif ($method !== null) {
            self::$middlewares[$controllerName][$method]['middlewares'] = $middlewares;
        } elseif ($version !== null) {
            self::$middlewares[$controllerName][$version]['middlewares'] = $middlewares;
        } elseif ($subDir !== null) {
            self::$middlewares[$controllerName][$subDir]['middlewares'] = $middlewares;
        }

        self::$middlewares[$controllerName]['middlewares'] = $middlewares;
    }

    /**
     * find route related middlewares
     *
     * @param Request $request
     * @return string[]
     */
    private static function getRouteMiddlewares(Request $request): array
    {
        $middlewares    = [];
        $controllerName = self::getControllerName($request);
        $actionName     = self::getAction($request);

        if (isset(self::$middlewares[$controllerName])) {
            $middlewares = self::$middlewares[$controllerName]['middlewares'];

            $dirs = self::getSubDirs($request);
            foreach ($dirs as $dir) {
                if (isset(self::$middlewares[$controllerName][$dir]['middlewares'])) {
                    $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$dir]['middlewares']);

                    if (isset(self::$middlewares[$controllerName][$dir][$actionName]['middlewares'])) {
                        $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$dir][$actionName]['middlewares']);
                    }

                    if (isset(self::$middlewares[$controllerName][self::getVersion($request)][$actionName]['middlewares'])) {
                        $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$dir][$actionName]['middlewares']);
                    }

                    if (isset(self::$middlewares[$controllerName][$dir][self::getVersion($request)]['middlewares'])) {
                        $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$dir][self::getVersion($request)]['middlewares']);

                        if (isset(self::$middlewares[$controllerName][$dir][self::getVersion($request)][$actionName]['middlewares'])) {
                            $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$dir][self::getVersion($request)][$actionName]['middlewares']);
                        }

                    }
                }

                if (isset(self::$middlewares[$controllerName][$actionName]['middlewares'])) {
                    $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$actionName]['middlewares']);
                }

                if (isset(self::$middlewares[$controllerName][self::getVersion($request)][$actionName]['middlewares'])) {
                    $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][$actionName]['middlewares']);
                }

                if (isset(self::$middlewares[$controllerName][self::getVersion($request)]['middlewares'])) {
                    $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][self::getVersion($request)]['middlewares']);

                    if (isset(self::$middlewares[$controllerName][self::getVersion($request)][$actionName]['middlewares'])) {
                        $middlewares = array_merge($middlewares, self::$middlewares[$controllerName][self::getVersion($request)][$actionName]['middlewares']);
                    }

                }

            }
        }

        return $middlewares;
    }

    /**
     * main function
     *
     * @param Request $request
     * @return void
     */
    public static function run(Request $request)
    {
        if ($request->getPathInfo() == '/') {
            return;
        }

        $middlewares = self::getRouteMiddlewares($request);

        //TODO: should cache result
        Route::middleware($middlewares)->group(function () use ($request) {
            $http_response = $request->method();
            Route::$http_response('/{any}', function () use ($request) {
                return \App::call([
                    \App::make(self::getNamspace($request) . '\\' . self::getControllerName($request)),
                    self::getAction($request),
                ]);
            })->where('any', '.*');
        });

    }
}
