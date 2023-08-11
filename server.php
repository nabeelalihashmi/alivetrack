<?php

header_remove("X-Powered-By");
$start = microtime(true);

use IconicCodes\LightView\LightView;
use Swoole\Http\Server;

include "config.php";
include "class/LightView.php";

$mode = 'prod';

$view = new LightView();
$view->views_path = __DIR__ . "/app/";
$view->includes_path = __DIR__ . "/app/includes/";
$view->cache_enabled = ($mode == 'prod');

function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit();
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit();
    }
}


function refresh($url) {
    echo "<meta http-equiv='refresh' content='0; url=$url'>";
}

function view($file, $data) {
    global $view;
    return $view->view($file, $data);
}


function json($data) {
    header('Content-Type: application/json');
    return json_encode($data);
}

function clearRouteName($route = '') {
    $route = trim(preg_replace('~/{2,}~', '/', $route), '/');
    return $route === '' ? '/' : "/{$route}";
}



function route($request, $response) {
    global $view;
    $uri = $request->server['path_info'];
    if ($uri == '/') {
        $uri = '/index';
    }


    $method = $request->server['request_method'];
    $method = strtolower($method);

    $success = false;

    $uriParts = explode('/', trim($uri, '/'));
    $class = array_shift($uriParts) ?? 'index';
    $class = preg_replace('/[^a-zA-Z0-9]/', '', $class);

    $classFile = 'app/pages/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        $fullyQualifiedClass = "Pages\\$class";
        if (class_exists($fullyQualifiedClass)) {
            $classMethod = array_shift($uriParts) ?? 'index';
            $args = $uriParts ?? [];
            $classMethod = preg_replace('/[^a-zA-Z0-9]/', '', $classMethod);
            $classMethod = $method . ucfirst($classMethod);
            if (method_exists($fullyQualifiedClass, $classMethod)) {
                $instance = new $fullyQualifiedClass();
                array_push($args, $request);
                array_push($args, $response);
                $output = call_user_func_array([$instance, $classMethod], $args);
                $success = true;
                if ($output !== null) {
                    echo $output;
                }
                if ($output === false) {
                    $success = false;
                }
            } else {
                $success = false;
            }
        }
    } elseif (file_exists('app' . $uri . '.php')) {
        $page = 'app' . $uri . '.php';
        $namespace = "app" . str_replace('/', "\\", $uri);
        include_once $page;
        $execMethod = $namespace . '\\' . $method;
        if (function_exists($execMethod)) {
            $output = $execMethod();
            $success = true;
            if ($output !== null) {
                echo $output;
            }
            if ($output === false) {
                $success = false;
            }
        }
    } elseif (file_exists('app/pages' . $uri . '.html')) {
        $page = ltrim($uri, '/') . '.html';
        $view->view('pages/' . $page);
        $success = true;
    } elseif (file_exists('app/pages/' . $uri . '/index.html')) {
        $page = ltrim($uri, '/') . '/index.html';
        $view->view('pages/' . $page);
        $success = true;
    }

    if (!$success) {
        $view->view('pages/404/index.html');
    }
}

$server = new Server('127.0.0.1', 8800);
$server->on('start', function ($server) {
    echo "Server started at http://127.0.0.1:8800\n";
});
$server->on('request', function ($request, $response) {
    // $uri = makeRequestUri();
    $uri = $request->server['path_info'];
    if ($uri == '/') {
        $uri = '/index';
    }

    ob_start();
    route($request, $response);
    $data = ob_get_clean();
    // $response->header('Content-Type', 'text/html');
    $response->end($data);
    // $response->end("Hello\n");
});
$server->start();
