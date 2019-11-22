<?php

$Gredis = Gredis::getInstance();
$tabela = 'cadastro';
$cod = 9999;
/*
* Geração da chave para o redis sql  + cod
*/
$gkey = $sql . '-' . $cod;
$gkey = $tabela . ':' . sha1($gkey);

if (!$Gredis->keyExists($gkey)) {
    $ret = $this->sqlExec($sql);
    $Gredis->redissetValue($gkey, $ret, 150);
} else {
    $ret = $Gredis->redisgetValue($gkey);
}
