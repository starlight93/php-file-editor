<?php
/**
 * zero dependency file operation
 */
class File {
    public $path;
    public $content;
    public $isExist = false;
    public $regexVar = '/{{(.*?)}}/';

    function __construct( string $path )
    {
        $this->path = $path;        
        $this->isExist = file_exists( $this->path );
        $this->content = $this->isExist? file_get_contents( $path ): '';
    }

    public function setContent( string $content ): void
    {
        $this->content=$content;
    }

    public function getMeta(): array
    {
        return [
            'owner' => posix_getpwuid( fileowner( $this->path ) )['name'],
            'user' => get_current_user(),
            'last_edited' => date( 'd/M/y H:i:s', filemtime($this->path) ),
            'path' => $this->path,
            'writable' => is_writable($this->path)
        ];
    }

    public function getContent( bool $highLighted=true ) :string
    {
        $content = $this->content;
        return !$highLighted ? $content: "
            <html>
                <header>
                    <link rel='stylesheet' href='/assets/monokai.min.css'>
                    <script src='/assets/highlight.min.js'></script>
                </header>
                <body style='background:#23241f'>
                    <script>
                        hljs.highlightAll();
                    </script>
                    <pre><code>".htmlentities($content)."</code></pre>
                </body>
            </html>
        ";
    }

    public function exists() :bool
    {
        return $this->isExist;
    }

    public function isDirectory() :bool
    {
        return is_dir($this->path);
    }

    public function from( string $path, array $data=[ /* 'key'=>'value' */ ] ) :self
    {
        $this->content = file_exists( $path ) || str_starts_with( strtolower($path), 'http' ) ? file_get_contents( $path ) : '';
        if( $data ) $this->replace( $data );
        return $this;
    }

    public function replace( array $data ) :self
    {
        $this->content = str_replace( array_keys( $data ), array_values( $data ), $this->content );
        return $this;
    }

    public function save() : void
    {
        $targetDirectory = dirname( $this->path );
        if ( !file_exists( $targetDirectory ) ) {
            mkdir($targetDirectory, 0755, true);
        }
        file_put_contents( $this->path, $this->content );
    }

    public function edit( callable $edit ) :self
    {
        $this->content = $edit( $this->content );
        return $this;
    }

    public function delete() :bool
    {
        if(is_writable($this->path)){
            unlink( $this->path );
            return true;
        }else{
            return false;
        }
    }

    public function isWritable() :bool
    {
        return is_writable($this->path);
    }

    public function getTimestamp() :string
    {
        return date("Ymd His", filemtime( filename: $this->path ));
    }

    public function getVariableKeys( string $patten=null ) :array
    {
        preg_match_all( ( $patten ?? $this->regexVar ), $this->content, $matches);
        return !empty( $matches[0] ) ? $matches[0]: [];
    }
}