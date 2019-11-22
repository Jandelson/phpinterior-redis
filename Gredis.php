<?php
/** Tratamento Cache com Redis
 *
 * @package   Geweb
 * @name      Gredis
 * @version   1.0.0
 * @copyright 2017 &copy; GeWeb Informatica Ltda
 * @link      http://www.geweb.com.br/
 * @author    Jandelson Oliveira <jandelson.oliveira at geweb dot com dot br>
 *
 * Documentacao:
 * https://github.com/phpredis/phpredis
 *
 */ 
class Gredis
{
    private $ip;
    private $port;
    public $redis;
    public $connected;
    private static $_instance;

    private $tabelasCache = [
        'cadastro' => ['cadastro']
    ];

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct($ip = '127.0.0.1', $port = 6379)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->connected = false;
        $this->redis = null;
        if (extension_loaded('redis')) {
            $this->redis = new \Redis();
            $this->connected = $this->connect();
        }
    }
    
    private function connect()
    {
        try {
            return @$this->redis->connect($this->ip, $this->port, 2.5, null, 150);
        } catch (\Exception $e) {
            return null;
        }
    }    
    /*
     * rediskey Cria chave com o valor informado para montar a chave para o redis
    */
    private function rediskey($value, $encr = false)
    {
        if ($encr) {
            $key = sha1($value);
        } else {
            $key = $value;
        }
        return $key;
    }

    /*
     * redissetValue Grava valores no Redis com chave valor e tempo de expiração da chave
    */
    public function redissetValue($key, $data, $ttl = 150)
    {
        $key = $this->rediskey($key);
        $data = json_encode($data);
        try {
            if ($ttl == 0) {
                $this->redis->set($key, $data);
            } else {
                $this->redis->setex($key, $ttl, $data);
            }
        } catch (Exception $e) {
            echo $e->getMessege();
        }
    }
    
    /*
     * redisgetValue Retorna valor da chave do redis em formato padrão (JSON)
    */
    public function redisgetValue($key)
    {
        $key = $this->rediskey($key);
        try {
            $data = $this->redis->get($key);
            return json_decode($data);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    /*
     * keyExists Verifica se a chave que está sendo criada no Redis já existe
    */
    public function keyExists($key)
    {
        $key = $this->rediskey($key);
        try {
            return $this->redis->get($key);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    private function likeKey($tabela)
    {
        return $this->redis->keys('*' . $tabela . '*');
    }
    
    private function deleteValue($key)
    {
        $key = $this->rediskey($key);
        try {
            $this->redis->del($key);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    /*
     * redisAnalise
     * Analisa se a tabela em questão que está sendo modificado faz parte de alguma api
     * caso sim analisa se tem cache da tabela no redis e se tiver ele deleta uma ou mais relações encontradas
     * altera o dado e ajusta a chave do redis
     *
     * @param string $sql SQL contendo nome das tabelas que podem estar no cache
     *
     * @return void
     */
    public function redisAnalise($sql)
    {
        $sql = strtolower($sql);
        $sql = preg_replace('/\s\s+/', ' ', $sql);
        $pos = strpos($sql, 'insert');
        if ($pos === false) {
            $pos = strpos($sql, 'update');
        }
        if ($pos === false) {
            $pos = strpos($sql, 'delete');
        }
        if ($pos !== false) {
            $palavras = explode(' ', $sql);
            foreach ($palavras as $k => $v) {
                $v = trim($v);
                if (!empty($v)) {
                    foreach ($this->tabelasCache as $k1 => $v1) {
                        if (in_array($v, $v1)) {
                            if (file_exists("/api/{$k1}.php")) {
                                $keys = $this->likeKey($k1);
                                if (count($keys) > 0) {
                                    foreach ($keys as $k2 => $v2) {
                                        $this->deleteValue($v2);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
