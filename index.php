<?php
ob_start();
require_once "Controllers/Controller.php";
function is_JSON($string) {
    return (is_null(json_decode($string))) ? FALSE : TRUE;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");

header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: Accept, Content-Type, Content-Length, Accept-Encoding, X-CSRF-Token, Authorization");
    }
    exit(0);
}

$env = parse_ini_file('.env');
$token = $env ? @$env['AUTHORIZATION_TOKEN'] : false;
if( $env['PREFIX_PATH'] === '__DIR__' ){
    $env['PREFIX_PATH'] = __DIR__;
}

if($_SERVER['REQUEST_METHOD']!='OPTIONS' && $token && (@$_SERVER['HTTP_AUTHORIZATION'])!=$token){
    http_response_code( 401 );
    $result = json_encode([
        'message'=>'Unauthenticated'
    ]);

    if( @$env['GZIP_COMPRESSED_RESPONSE'] ){
        echo gzcompress( $result, 9);
    }else{
        echo $result;
    }
    die();
}

$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$pathStr = parse_url( $url ) ['path'];
$path = ($endpoints = explode("/", $pathStr))[1];
if(!$path) $path = 'index';

$bodyArr = [];
$method = $_SERVER['REQUEST_METHOD'];

if( $method == 'GET' ){
    $queryStr = @$_SERVER['QUERY_STRING']??'';
    parse_str(  $queryStr, $bodyArr);
}elseif( $method == 'POST' ) {
    $bodyTxt = file_get_contents('php://input')??"[]";
    $bodyArr = json_decode($bodyTxt, true)??[];
    foreach( $_FILES as $key => $file ){
        if( str_contains( $file['type'], 'gzip' ) ){
            $decoded = gzdecode(file_get_contents($file['tmp_name']));
            try{
                $jsonData = json_decode( $decoded, true );
                foreach($jsonData as $keyJson => $value){
                    $bodyArr[$keyJson] = $value;
                }
            }catch(\Exception $err){
                $bodyArr[$key] = $decoded;
            }
        }
    }
}

$controller = new Controller( $env );
if (method_exists($controller, $path)) {
    $res = call_user_func_array([$controller, $path], [ 'param'=>@$endpoints[2]??'', 'request'=>  (object)$bodyArr ] );
    if(is_array($res)){
        header('Content-Type: application/json');
        $result = json_encode( $res );
    }else{
        if(!str_contains($res, '<html')){
            header('Content-Type: application/json');
        }
        $result = $res;
    }

    if( @$env['GZIP_COMPRESSED_RESPONSE'] ){
        echo gzcompress( $result, 9);
    }else{
        echo $result;
    }
} else {
    http_response_code( 404 );
    echo json_encode([
        'message'=>'Not Found',
        'path' => $path
    ]);
}