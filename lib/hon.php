<?php
/**
 * @copyright Copyright Colin 'riyuk' Böttcher 2012
 * @author Colin 'riyuk' Böttcher
 * @version 0.1
 */
/**
 * Usage:
 *
 * hon::get()->function( array( params ) )
 *
 * If you need to get authenticated before you can use the function
 * you should do hon::get()->auth()->...
 *
 */
final class hon {

    const COOKIE = 'cookie';

    /**
     * @var hon $_instance
     * @since 0.1
     */
    private static $_instance;
    /**
     * @var string URL to Client-API
     * @since 0.1
     */
    private static $_clientURL = 'http://masterserver.hon.s2games.com/client_requester.php';
    /**
     * @var string URL to Patcher-API
     * @since 0.1
     */
    private static $_patcherURL = 'http://masterserver.hon.s2games.com/patcher/patcher.php';
    /**
     * You need to deliver the needed parameters (except "cookie") i.e:
     * array( 'auth' => array( 'login' => 'username', 'password' => 'mypassword' ) )
     *
     * @var array Valid functions
     * @since 0.1
     */
    private static $_functions = array(
        'auth' => array(
            'login', 'password'
        ),
        'autocompleteNicks' => array(
            'nickname'
        ),
        'nick2id' => array(
            'nickname[0]'
        ),
        'get_all_stats' => array(
            'account_id[0]'
        ),
        'grab_last_matches' => array(
            'account_id'
        ),
        'get_match_stats' => array(
            'cookie', 'match_id[0]'
        ),
        'new_buddy' => array(
            'cookie', 'account_id' => 'self', 'buddy_id'
        ),
        'remove_buddy' => array(
            'cookie', 'account_id' => 'self', 'buddy_id'
        )
    );
    /**
     * @var bool is authenticated?
     * @since 0.1
     */
    private $_auth = false;
    /**
     * @var array user-object of logged in user
     * @since 0.1
     */
    private $_me = array();
    /**
     * @var bool set debugging
     * @since 0.1
     */
    private $_debug = false;


    /**
     * Singleton
     *
     * @static
     * @return hon
     * @since 0.1
     */
    public static function get() {
        if( null === self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Magic Calls to valid functions
     *
     * @param string $func
     * @param array $args
     * @return mixed
     * @throws Exception
     * @since 0.1
     */
    public function __call( $func, $args = array() ) {
        if( !isset( self::$_functions[$func] ) ) {
            throw new Exception( 'Invalid Function Call' );
        }

        $arg = array();
        if( is_array( $args ) && count( $args ) ) {
            for( $i = 0; $i < count( $args ); $i++ ) {
                $arg[key( $args[$i] )] = $args[$i][key( $args[$i] )];
            }
        }

        $func = array( $func => $arg );
        $ret = $this->_fetch( $func );
        if( $this->_debug ) {
            $this->_debug( $ret );
        }
        return $ret;
    }

    /**
     * Set debugging (true/false)
     *
     * @param bool $bool
     * @return hon
     * @since 0.1
     */
    public function debug( $bool ) {
        $this->_debug = !!($bool);
        return $this;
    }

    /**
     * Outputs the given arguments
     *
     * @param $mixed
     * @return void
     * @since 0.1
     */
    private function _debug( $mixed ) {
        echo '<pre>';
        if( is_string( $mixed ) ) {
            echo trim( $mixed );
        } else {
            var_dump( $mixed );
        }
        echo '</pre>';
    }

    /**
     * Some functions are restricted to logged-in users
     * You can assign your password with md5( $passwd ) and set the $isMd5 to true
     * if you don't want your plain password in the source code.
     *
     * WARNING: DO NEVER PASTE YOUR PASSWORD ON ANY WEBSITE - NEITHER MD5 OR PLAIN!
     *
     * @param string $username
     * @param string $password
     * @param bool $isMd5
     * @return hon
     * @throws Exception
     * @since 0.1
     */
    public function auth( $username, $password, $isMd5 = false ) {
        $resp = $this->_fetch( array(
            'auth' => array(
                'login' => $username,
                'password' => ( $isMd5 ? $password : md5( $password ) ),
            ),
        ) );

        if( $resp === false ) {
            throw new Exception( 'Fehler beim Decode' );
        }

        if( isset( $resp['error'] ) ) {
            throw new Exception( $resp['error'] );
        }

        $this->_me = $resp;
        $this->_auth = true;

        return $this;
    }

    /**
     * Returns all informations about the current logged-in user
     *
     * @return array
     * @throws Exception
     */
    public function me() {
        if( !$this->_auth || !count( $this->_me ) ) {
            throw new Exception( 'You need to authenticate first' );
        }
        return $this->_me;
    }

    /**
     * Returns the current Server Version available
     *
     * @return string
     * @since 0.1
     */
    public function version() {
        $fetch = $this->_fetch( array( 'latest' => '', 'os' => 'wac', 'arch' => 'i686' ), 'patcher' );
        if( is_array( $fetch ) && count( $fetch ) ) {
            return current( $fetch );
        }
        return '';
    }

    /**
     * Correct some URL behaviors
     *
     * @static
     * @param $str
     * @return mixed
     * @since 0.1
     */
    private static function _urlencode_rfc3986( $str ) {
        return str_replace(
                '+',
                ' ',
                str_replace( '%7E', '~', rawurlencode( $str ) )
              );
    }

    /**
     * Used for all function calls.
     * Validates them and returns the response
     *
     * @param array $params
     * @param string $url
     * @return bool|array
     * @throws Exception
     * @since 0.1
     */
    private function _fetch( $params, $url = 'client' ) {
        if( $url == 'client' ) {
            $url = self::$_clientURL;
        } else {
            $socks = $this->_requestSocks( self::$_patcherURL, $params, $this->_debug );
            return unserialize( trim( $socks ) );
        }

        $function = current( array_keys( $params ) );
        if( !isset( self::$_functions[$function] ) ) {
            throw new Exception( 'Invalid Function' );
        }

        if( in_array( self::COOKIE, self::$_functions[$function] ) ) {
            if( !$this->_auth ) {
                throw new Exception('You need to authenticate first!');
            } else {
                $params[self::COOKIE] = $this->_me['cookie'];
            }
        }

        if( count( self::$_functions[$function] ) ) {
            foreach( self::$_functions[$function] as $key => $val ) {
                if( !is_numeric( $key ) && $val == 'self' && $this->_auth ) {
                    $params[$function][$key] = $this->_me['account_id'];
                    continue;
                }
                if( !isset( $params[$function][$val] ) && $val != self::COOKIE ) {
                    throw new Exception( 'At least one Parameter is missing: ' . $val );
                } else if( $params[$function][$val] == 'self' && $this->_auth ) {
                    $params[$function][$val] = $this->_me['account_id'];
                }
            }
        }

        if( isset( $params[self::COOKIE] ) ) {
            $cookie = $params[self::COOKIE];
        } else {
            $cookie = '';
        }

        $params = array_merge( array( 'f' => $function ), $params[$function] );
        unset( $params[$function] );
        if( strlen( $cookie ) ) {
            $params[self::COOKIE] = $cookie;
        }

        $socks = $this->_requestSocks( $url, $params, $this->_debug );

        if( strstr( trim( $socks ), "\r\n" ) ) {
            $lines = explode( "\r\n", trim( $socks ) );
            foreach( $lines as $line ) {
                if( @unserialize($line) !== false ) {
                    return unserialize( $line );
                }
            }
        }
        return unserialize( trim( $socks ) );
    }

    /**
     * HTTP-Request (uses fsockopen instead of curl)
     *
     * @param string $url
     * @param array $params
     * @param bool $verbose
     * @return string
     * @throws Exception
     * @since 0.1
     */
    private function _requestSocks( $url, $params = array(), $verbose = false ) {

        $urlParse = parse_url( $url );
        if( isset( $urlParse['host'] ) && strlen( $urlParse['host'] ) ) {
            $host = $urlParse['host'];
        } else {
            throw new Exception('something wrong');
        }


        $verb = count( $params ) ? 'POST' : 'GET';
        $crlf = "\r\n";
        $req = $verb . ' ' .  $url . ' HTTP/1.1' . $crlf;
        $req .= 'Host: ' . $host . $crlf;
        $req .= 'User-Agent: Mozilla/5.0 Firefox/3.6.12' . $crlf;
        $req .= 'Accept: */*' . $crlf;
        #$req .= 'Accept-Encoding: gzip,deflate,sdch' . $crlf;
        $req .= 'Cache-Control: no-cache' . $crlf;
        $req .= 'Pragma: no-cache' . $crlf;
        $req .= 'Accept-Charset: ISO-8859-1,utf-8,us-ascii;q=0.7,*;q=0.7' . $crlf;
        if( $verb == 'POST' ) $req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
        $req .= 'Connection: close' . $crlf;

        if( $verb == 'POST' ) {
            if( is_array( $params ) && count( $params ) ) {
                $post = '';
                foreach( $params as $key => $value ) {
                    $post .= $key . '=' . self::_urlencode_rfc3986( $value ) . '&';
                }
                $post = substr( $post, 0, -1 );
            } else {
                $post = self::_urlencode_rfc3986( $params );
            }

            $req .= 'Content-Length: '. strlen( $post ) . $crlf . $crlf;
            $req .= $post . $crlf . $crlf;
        } else $req .= $crlf;

        if( ( $fp = fsockopen( $host, 80, $errno, $errstr, 30 ) ) == false ) {
            return "ERROR - ErrNo: " . $errno . ' - ErrStr: ' . $errstr;
        }

        if( $verbose ) {
            $ret = "Request (Host: " . $host . " | Port: 80):" . $crlf . $req . 'Response:' . $crlf;
        } else {
            $ret = '';
        }

        fputs( $fp, $req );
        while( $line = fgets( $fp ) ) $ret .= $line;
        fclose( $fp );

        if( !$verbose ) {
            $ret = substr($ret, strpos($ret, "\r\n\r\n") );
        }
        return $ret;
    }
}
