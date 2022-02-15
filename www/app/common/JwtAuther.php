<?php
namespace app\common;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
class JwtAuther
{
 
    private $token;

    private $iss = 'www.one6666.com';

    private $aud = 'www.gsznzb.com';

    private $uid;
   
    private $secret = '(&*99979)';

    private $decodeToken;

    private static $instance;

    public static function getInstance()
    {
        if(is_null(self::$instance)){
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function setUid($uid){
        $this->uid = $uid;
        return $this;
    }

    public function getUid(){
        return $this->uid;
    }

    public function getToken()
    {
        return (string)$this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function encode()
    {
        $signer = new Sha256();
        $time = time();
        $this->token = (new Builder())
                                ->issuedBy($this->iss) // Configures the issuer (iss claim)
                                ->permittedFor($this->aud) // Configures the audience (aud claim)
                                ->identifiedBy('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
                                ->issuedAt($time) // Configures the time that the token was issue (iat claim)
                                ->canOnlyBeUsedAfter($time + 60) // Configures the time that the token can be used (nbf claim)
                                // ->expiresAt($time + 36000) // Configures the expiration time of the token (exp claim)
                                ->expiresAt($time + 999999) // Configures the expiration time of the token (exp claim)
                                ->withClaim('uid', $this->uid) // Configures a new claim, called "uid"
                                ->getToken($signer,new Key($this->secret)); // Retrieves the generated token

        return $this;
        // $this->token->getHeaders(); // Retrieves the token headers
        // $this->token->getClaims(); // Retrieves the token claims

        // echo $this->token->getHeader('jti'); // will print "4f1g23a12aa"
        // echo $this->token->getClaim('iss'); // will print "http://example.com"
        // echo $this->token->getClaim('uid'); // will print "1"
        // echo $this->token; // The string representation of the object is a JWT string (pretty easy, right?)
    }

    public function decode(){
        if($this->decodeToken){
            return $this->decodeToken;
        }
        
        $this->decodeToken = (new Parser())->parse((string) $this->token); // Parses from a string
        $this->uid = $this->decodeToken ->getClaim('uid');
        return $this;
        // $this->decodeToken ->getHeaders(); // Retrieves the token header
        // $this->decodeToken ->getClaims(); // Retrieves the token claims

        // echo $this->decodeToken ->getHeader('jti'); // will print "4f1g23a12aa"
        // echo $this->decodeToken ->getClaim('iss'); // will print "http://example.com"
        // echo $this->decodeToken ->getClaim('uid'); // will print "1"
    }

    public function validate(){
        $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
        $data->setIssuer($this->iss);
        $data->setAudience($this->aud);
        $data->setId('4f1g23a12aa');
        $data->setCurrentTime(time() + 61);
        $res= $this->decode()->validate($data);
        return $res;
        // var_dump($this->decode()->validate($data)); // false, because token cannot be used before now() + 60

        // $data->setCurrentTime($time + 61); // changing the validation time to future

        // var_dump($this->validate($data)); // true, because current time is between "nbf" and "exp" claims

        // $data->setCurrentTime($time + 4000); // changing the validation time to future

        // var_dump($this->validate($data)); // false, because token is expired since current time is greater than exp
    }

    public function verify(){
        $signer = new Sha256();

        $result = $this->decode()->verify($signer,$this->secret);
        return $result;
        // dump($result);
        // $token = (new Builder())->issuedBy($this->iss) // Configures the issuer (iss claim)
        //                         ->permittedFor($this->aud) // Configures the audience (aud claim)
        //                         ->identifiedBy('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
        //                         ->issuedAt($time) // Configures the time that the token was issue (iat claim)
        //                         ->canOnlyBeUsedAfter($time + 60) // Configures the time that the token can be used (nbf claim)
        //                         ->expiresAt($time + 3600) // Configures the expiration time of the token (exp claim)
        //                         ->withClaim('uid', $this->uid) // Configures a new claim, called "uid"
        //                         ->getToken($signer, new Key($this->secret)); // Retrieves the generated token
        
        
        // var_dump($token->verify($signer, 'testing 1')); // false, because the key is different
        // var_dump($token->verify($signer, 'testing')); // true, because the key is the same
    }
    
}