<?php

$Gredis = Gredis::getInstance();
$tabela = 'cadastro';
$cod = 9999;
$timekey = 3600;
/*
* Geração da chave para o redis sql  + cod
*/
$gkey = $sql . '-' . $cod;
$gkey = $tabela . ':' . sha1($gkey);

/*
* Analisa se o sql é insert.update ou delete
* Se positivo analisa se existe a tabela na lista de tabela de cache $tabelasCache
* Depois analisa se existe uma api para essa tabela, pois apenas apis são aceitas no cache do redis
* Se existir a api, analisa se existem chaves no redis para essa tabela e exclui a chave
*/
$Gredis->redisAnalise($sql)

if (!$Gredis->keyExists($gkey)) {
    $ret = $this->sqlExec($sql);
    $Gredis->redissetValue($gkey, $ret, $timekey);
} else {
    $ret = $Gredis->redisgetValue($gkey);
}
