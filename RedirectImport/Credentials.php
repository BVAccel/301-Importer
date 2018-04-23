<?php namespace RedirectImport;

use Tightenco\Collect\Support\Collection;

class Credentials {
    private $creds;
    private $credString;
    public function __construct( $string ) {
        $this->credString = $string;
        $parts = preg_split( '/(.*):(.*)@(.*)/', $string );
        $parts = explode( ':', $string, 2 );
        $parts = array_merge( [ $parts[0] ] , explode( '@', $parts[1] ) );
        $this->creds = new Collection( [
            'key' => $parts[0],
            'pass' => $parts[1],
            'domain' => $parts[2],
        ] );
    }

    /**
     * @return mixed
     */
    public function getCreds() {
        return $this->creds;
    }

    /**
     * @param mixed $creds
     */
    public function setCreds( $creds ): void {
        $this->creds = $creds;
    }

    /**
     * @return mixed
     */
    public function getCredString() {
        return $this->credString;
    }
}