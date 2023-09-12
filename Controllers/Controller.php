<?php

include __DIR__."/../Lib/File.php";
include __DIR__."/../Lib/Folder.php";

class Controller {
    protected $env = [];

    function __construct( array $env )
    {
        $this->env = $env;
    }

    protected function isPathWritable( string $path ){
        $writablePaths = explode( ",", @$this->env['WRITABLE_PATHS'] ?? '' );
        foreach( $writablePaths as $writablePath ){
            if( str_starts_with($path, $writablePath) ){
                return true;
            }
        }
        return false;
    }

    public function index( string $param,object $request )
    {

        $protocol = @$_SERVER['HTTPS']? 'https://': 'http://';
        return [
            "Desicription" => "File & Directory Native PHP Editor",
            "Repo" => "https://github.com/starlight93/php-file-editor.git",
            "current_environment"=>[
                "PREFIX_PATH"=>@$this->env ['PREFIX_PATH']??'',
                "IS_GZIP_COMPRESSED"=>@$this->env ['GZIP_COMPRESSED_RESPONSE']?true:false
            ],
            "routes" =>[
                "/getStructure" =>[
                    "url" => $protocol.$_SERVER['HTTP_HOST']."/getStructure?path=/",
                    "method" => "GET/POST",
                    "query_or_body" => [
                        "path" => "string (required), example: /",
                        "nested" => "true(default), format response to nested folder or list only",
                        "search" => "string (optional), search file by matching their filename against keyword",
                        "contain" => "string (optional), search file by matching their content contain keyword"
                    ],
                    "description" => "List Files & Directories Recursively",
                ],
                "/getContent" =>[
                    "method" => "GET/POST",
                    "query_or_body" => [
                        "path" => "string (required), example: /myfile.txt",
                        "highlight" => "true/false, default:true"
                    ],
                    "description" => "Get text content of a file",
                ],
                "/setContent" =>[
                    "method" => "POST",
                    "body" => [
                        "path" => "string (required), example: /home/users/downloads/myfile.txt",
                        "content" => "text (optional), example: 'this is a content', default text content",
                        "from" => "string (optional), example: /home/users/downloads/myfile.txt, copy template from another file",
                        "data" => "object (optional), example: { 'mustReplaced':'value' }, replace content by keyword"
                    ],
                    "description" => "Set text content to a new or existing file",
                ],
                "/getMeta" =>[
                    "method" => "GET/POST",
                    "query_or_body" => [
                        "path" => "string (required), example: /home/users/downloads/myfile.txt"
                    ],
                    "description" => "Get Meta Data of The File or Folder",
                ],
                "/delete" =>[
                    "method" => "GET/POST",
                    "query_or_body" => [
                        "path" => "string (required), example: /home/users/downloads/myfile.txt"
                    ],
                    "description" => "Unlink File or Empty Directory",
                ]
            ]
        ];
    }

    public function getStructure( string $param, object $request )
    {
        if(!@$request->path){
            http_response_code( 422 );
            return [
                'message'=>'`path` param is required'
            ];
        }

        $protocol = @$_SERVER['HTTPS']? 'https://': 'http://';
        $path = $request->path;
        $prefixPath = @$this->env ['PREFIX_PATH'];
        if( $prefixPath && !str_starts_with($path, $prefixPath) ) $path = $prefixPath.$path;

        return Folder::getStructure( $path, null, function($fileName, $basePath)use($protocol){
            return $protocol.$_SERVER['HTTP_HOST']."/getContent?path=$basePath$fileName";
        }, @$request->search??'', @$request->contain??'', !@$request->nested || @$request->nested=='true'?true:false );
    }

    public function getMeta( string $param, object $request )
    {
        if(!@$request->path){
            http_response_code( 422 );
            return [
                'message'=>'`path` param is required'
            ];
        }
        $path = $request->path;
        $prefixPath = @$this->env ['PREFIX_PATH'];
        if( $prefixPath && !str_starts_with($path, $prefixPath) ) $path = $prefixPath.$path;

        $file = new File( $path);
        $meta = $file->getMeta();
        $meta['writable'] = $meta['writable'] ?? $this->isPathWritable($path);
        return $meta;

    }

    public function getContent( string $param, object $request )
    {
        if(!@$request->path){
            http_response_code( 422 );
            return [
                'message'=>'`path` param is required'
            ];
        }
        $path = $request->path;
        $prefixPath = @$this->env ['PREFIX_PATH'];
        if( $prefixPath && !str_starts_with($path, $prefixPath) ) $path = $prefixPath.$path;
        $pathArr = explode( "/", $path );

        if( str_starts_with( end($pathArr), '.') ){
            http_response_code( 401 );
            return [
                'message'=>'Unauthorized'
            ];
        }
        $file = new File( $path);
        return $file->getContent( @$request->highlight=='false' ? false: true );
    }

    public function setContent( string $param, object $request )
    {
        if(!@$request->path){
            http_response_code( 422 );
            return [
                'message'=>'`path` body key is required'
            ];
        }
        // $request (path, from,content,data)
        $path = $request->path;
        $prefixPath = @$this->env ['PREFIX_PATH'];
        if( $prefixPath && !str_starts_with($path, $prefixPath) ) $path = $prefixPath.$path;
        $pathArr = explode( "/", $path );

        if( str_starts_with( end($pathArr), '.')||!$this->isPathWritable($path) ){
            http_response_code( 401 );
            return [
                'message'=>'Unwritable Path'
            ];
        }

        if( !str_contains( end($pathArr), '.') ){
            http_response_code( 422 );
            return [
                'message'=>'File must have an extension separated by . (dot)'
            ];
        }

        $file = new File($path);
        
        if( !$file->isWritable() ){
            http_response_code( 401 );
            return [
                'message'=>'No permission to write to this path'
            ];
        }

        if( isset($request->from) ){
            $file->from( $request->from );
        }

        if( isset($request->content) ){
            $file->setContent( $request->content );
        }
        
        if( isset($request->data) ){
            $file->replace( $request->data );
        }
        
        $file->save();

        return $file->getContent();
    }

    public function delete( string $param, object $request )
    {
        if(!@$request->path){
            http_response_code( 422 );
            return [
                'message'=>'`path` param is required'
            ];
        }
        $path = $request->path;
        $prefixPath = @$this->env ['PREFIX_PATH'];
        if( $prefixPath && !str_starts_with($path, $prefixPath) ) $path = $prefixPath.$path;
        $pathArr = explode( "/", $path );

        if( str_starts_with( end($pathArr), '.')||!$this->isPathWritable($path) ){
            http_response_code( 401 );
            return [
                'message'=>'Undeletable Path'
            ];
        }

        $file = new File($path);

        if( !$file->isWritable() ){
            http_response_code( 401 );
            return [
                'message'=>'No permission to delete this path'
            ];
        }

        if( !$file->exists() ){
            http_response_code( 404 );
            return [
                'message'=>'File does not exist',
                'path' => $path
            ];
        }
        
        if( $file->isDirectory() ){

            if( !Folder::isEmpty( $path ) ){
                http_response_code( 401 );
                return [
                    'message'=>'Directory not empty'
                ];
            }

            $res = Folder::delete( $path );
        }else{
            $res = $file->delete();
        }
        
        if(!$res){
            http_response_code( 401 );
            return [
                'message'=>'Unable to delete the file',
                'path' => $path
            ];
        }

        return [
            'message'=>'File was deleted successfully',
            'path' => $path
        ];
    }
}