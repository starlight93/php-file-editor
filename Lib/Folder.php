<?php

/**
 * zero dependency
 * usage: Folder::getStructure( '/home/x/y/z/, '/app')
 */
class Folder {

    public static $basePath = '';
    public static $relativePath = '';
    public static $callbackFileName;
    public static $searchName = '';
    public static $searchContent = '';
    public static $nestedFormat = true;
    protected $forbidden = [
        "vendor", "node_modules", "dist"
    ];

    public static function getStructure( 
        string $basePath, string $relativePath=null, callable $callback=null, string $searchName='', 
        string $searchContent='', bool $nestedFormat=true 
    ) :array {
        self::$searchName = $searchName;
        self::$searchContent = $searchContent;
        self::$nestedFormat = $nestedFormat;
        self::$basePath = $relativePath?$basePath: self::getBasePath( $basePath );
        self::$relativePath = $relativePath?$relativePath: self::getPath( $basePath );
        self::$callbackFileName = $callback??function(string $fileName, string $basePath){
            return $fileName;
        };
        return (new self)->getList( str_replace( '//', '/', $basePath.$relativePath ) )[''];
    }

    public function getList( string $dir, &$results = [] ) :array
    {
        usort($results, function($a, $b) {
            $isFolderA = is_dir($a);
            $isFolderB = is_dir($b);
        
            // Sort folders before files
            if ($isFolderA && !$isFolderB) {
                return -1;
            } elseif (!$isFolderA && $isFolderB) {
                return 1;
            }
        
            // If both are folders or both are files, sort them alphabetically
            return strcasecmp($a, $b);
        });
        
        $files = preg_grep('/^([^.])/', scandir($dir));
        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if( in_array($value, $this->forbidden) ) continue;
            if(!is_dir($path) && (!self::$searchName || (self::$searchName && str_contains($value, self::$searchName) ))) {
                if(self::$searchContent){
                    $content = file_get_contents($path);
                    if(str_contains( $content, self::$searchContent )){
                        $results[] = $path;
                    }else{
                        continue;
                    }
                }else{
                    $results[] = $path;
                }
            } else if($value != "." && $value != ".." && is_dir($path)) {
                $this->getList ( $path, $results );
                if(  !self::$searchContent && (!self::$searchName || (self::$searchName && str_contains($value, self::$searchName) ))){
                    $results[] = $path;
                }
            }
        }
        return !self::$nestedFormat? $results: $this->formatStructure( $results );
    }

    public function formatStructure( array $data ) :array
    {
        $result = [];
    
        foreach ($data as $path) {
            $path = str_replace(self::$basePath, '', $path);
            $parts = explode('/', $path);
            $current = &$result;
            
            foreach ($parts as $key => $part) {
                if (!isset($current[$part]) ) {
                    if($current[$part] = str_contains( $part, "." )){
                        $func = self::$callbackFileName;
                        $current[$part] = $func($path, self::$basePath);
                    }else{
                        $current[$part] = [];
                    }
                }
    
                $current = &$current[$part];
            }
        }
    
        return $result;
    }

    public function searchFiles( string $keyword ) :array
    {
        return [];
    }

    public static function getBasePath( string $path ) :string
    {
        $pathArr = explode( "/", $path );
        unset($pathArr[count($pathArr)-1] );
        return implode("/", $pathArr);
    }

    public static function getPath( string $path ) :string
    {
        $pathArr = explode( "/", $path );
        return end($pathArr);
    }

    public static function delete( string $path ): bool
    {
        if( !is_dir($path) ) return false;
        return rmdir( $path );
    }


    public static function isEmpty( string $dir ): bool
    {
        return (count(scandir($dir)) == 2);
    }
}