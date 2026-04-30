<?php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

return function () {
    $request = Request::createFromGlobals();

    // the URI being requested (e.g. /about) minus any query parameters
    $request->getPathInfo();

    // retrieves $_GET and $_POST variables respectively
    $request->query->get('id');
    $request->getPayload()->get('category', 'default category');

    // retrieves $_SERVER variables
    $request->server->get('HTTP_HOST');

    // retrieves an instance of UploadedFile identified by "attachment"
    $request->files->get('attachment');

    // retrieves a $_COOKIE value
    $request->cookies->get('PHPSESSID');

    // retrieves an HTTP request header, with normalized, lowercase keys
    $request->headers->get('host');
    $request->headers->get('content-type');

    $request->getMethod();    // e.g. GET, POST, PUT, DELETE or HEAD
    $request->getLanguages(); // an array of languages the client accepts

    $response = new Response(json_encode($request->query->all()), 200, ['Content-Type' => 'application/json']);
    $response->send();
};
