<?php
header_remove("X-Powered-By");
$start = microtime(true);

use IconicCodes\LightView\LightView;

include "app/config/general.php";

include "class/TinyHTML.php";
include "class/LightView.php";

$configs = [];
$mode = "dev";

$view = new LightView();
$view->views_path = __DIR__ . "/app/";
$view->includes_path = __DIR__ . "/app/includes/";
$view->cache_enabled = $mode == "prod";

function redirect($url)
{
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo "</script>";
        echo "<noscript>";
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo "</noscript>";
        exit();
    }
}

function refresh($url)
{
    echo "<meta http-equiv='refresh' content='0; url=$url'>";
}

function view($file, $data)
{
    global $view;
    return $view->view($file, $data);
}

function json($data)
{
    header("Content-Type: application/json");
    return json_encode($data);
}

function clearRouteName($route = "")
{
    $route = trim(preg_replace("~/{2,}~", "/", $route), "/");
    return $route === "" ? "/" : "/{$route}";
}

function makeRequestUri()
{
    // $requestURI = strtolower($_SERVER['REQUEST_URI']);
    // if (strpos($requestURI, '.') !== false) {
    //     return false;
    // }
    $requestURI = $_SERVER["REQUEST_URI"];
    $script = $_SERVER["SCRIPT_NAME"];
    $dirname = dirname($script);
    $dirname = $dirname === "/" ? "" : $dirname;
    $basename = basename($script);
    $uri = str_replace([$dirname, $basename], "", $requestURI);
    $uri = clearRouteName(explode("?", $uri)[0]);
    define("URI", $uri);
    return $uri;
}

$uri = makeRequestUri();

if (strpos($uri, "__layout") !== false) {
    $view->view("pages/404.html");
    return;
}

if ($uri == "/") {
    $uri = "/index";
}

$method = $_SERVER["REQUEST_METHOD"];
$method = strtolower($method);

$success = false;

// function customAutoloader($className) {
//     $file = __DIR__ . '/app/pages/' . str_replace('\\', '/', $className) . '.php';
//     if (file_exists($file)) {
//         require $file;
//     }
// }

// spl_autoload_register('customAutoloader');

$uriParts = explode("/", trim($uri, "/"));
$class = array_shift($uriParts) ?? "index";
$class = preg_replace("/[^a-zA-Z0-9]/", "", $class);

$classFile = "app/pages/" . $class . ".php";
if (file_exists($classFile)) {
    require_once $classFile;
    $fullyQualifiedClass = "Pages\\$class";
    if (class_exists($fullyQualifiedClass)) {
        $classMethod = array_shift($uriParts) ?? "index";
        $args = $uriParts ?? [];
        $classMethod = preg_replace("/[^a-zA-Z0-9]/", "", $classMethod);
        $classMethod = $method . ucfirst($classMethod);
        if (method_exists($fullyQualifiedClass, $classMethod)) {
            $instance = new $fullyQualifiedClass();
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
} elseif (file_exists("app" . $uri . ".php")) {
    $page = "app" . $uri . ".php";
    $namespace = "app" . str_replace("/", "\\", $uri);
    include $page;
    $execMethod = $namespace . "\\" . $method;
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
} elseif (file_exists("app/pages" . $uri . ".html")) {
    $page = ltrim($uri, "/") . ".html";
    $view->view("pages/" . $page);
    $success = true;
} elseif (file_exists("app/pages/" . $uri . "/index.html")) {
    $page = ltrim($uri, "/") . "/index.html";
    $view->view("pages/" . $page);
    $success = true;
} elseif (file_exists("app/pages" . $uri . ".md")) {
    $page = ltrim($uri, "/") . ".md";
    $view->view("pages/" . $page);
    $success = true;
}

if (!$success) {
    $view->view("pages/404/index.html");
}
// echo '<div style="border: 1px solid #333;position: fixed; left: 0; bottom: 0; z-index: 9999; padding: 10px; background-color: white">';
// $memory_size = memory_get_usage();
// $memory_size2 = memory_get_peak_usage();
// // Display memory size into kb, mb etc.
// echo 'Time: ' . (microtime(true) - $start) . "<br>";
// echo 'Used Memory :' . $memory_size  / (1024) . "kb<br>";
// echo 'Peak Memory : ' . $memory_size2 / (1024) . "kb\n";
// echo '</div>';
