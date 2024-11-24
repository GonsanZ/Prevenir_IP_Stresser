<?php
//Limite de pedido
function rate_limit_request()
{
    if (!class_exists("Redis") and !class_exists("Memcached")) {
        echo '<br><h1>Advertencia importante:</h1>
 <h2>necesitas "Redis" o "Memcached" en extensión PHP para activación.</h2>
    <br>';
    }

    $numberof_request = 5; //Número de solicitud
    $requestin_period = 10; //Por segundos
    $timeof_block = 60; //Tiempo de bloqueo
    //ej: Si una IP envía 5 solicitudes en 10 segundos, se bloquea durante 60 segundos.
    function blocked_page($ip)
    {
        echo '
<br><p style="text-align:center"><strong><span style="font-size:16px;font-family:Courier New,Courier">Tu petición (' .
            $ip .
            ')  está bloqueado temporalmente! Espere unos segundos y vuelva a intentarlo.</span></strong></p>
<p style="text-align:center">&nbsp;</p>
<p style="text-align:center"><span style="font-size:16px;font-family:Courier New,Courier">Desarrollado por <a href="https://github.com/GonsanZ/Prevenir_IP_Stresser" target="_blank">Limite de pedido</a></span></p>';
    }

    $total_user_calls = 0;
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $user_ip_address = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $user_ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else {
        $user_ip_address = $_SERVER["REMOTE_ADDR"];
    }
    if (class_exists("Redis")) {
        $redis = new Redis();
        $redis->connect("localhost", 6379);
        if (!$redis->exists($user_ip_address)) {
            $redis->set($user_ip_address, 1);
            $redis->expire($user_ip_address, $requestin_period);
            $total_user_calls = 1;
        } else {
            $redis->INCR($user_ip_address);
            $total_user_calls = $redis->get($user_ip_address);
            if ($total_user_calls > $numberof_request) {
                blocked_page($user_ip_address);
                $redis->set($user_ip_address, $numberof_request);
                $redis->expire($user_ip_address, $timeof_block);
                exit();
            }
        }
    } elseif (class_exists("Memcached")) {
        $memc = new Memcached();
        $memc->addServer("localhost", 11211);
        $item = $memc->get($user_ip_address);
        if ($memc->getResultCode() == Memcached::RES_SUCCESS) {
            $total_user_calls = $memc->get($user_ip_address);
            $total_user_calls = $total_user_calls + 1;
            $memc->replace($user_ip_address, $total_user_calls);
            if ($total_user_calls > $numberof_request) {
                blocked_page($user_ip_address);
                $memc->set($user_ip_address, $numberof_request, $timeof_block);
                exit();
            }
        } else {
            $memc->set($user_ip_address, 1, $requestin_period);
        }
    }
}
