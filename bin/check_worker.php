<?php

use App\Rabbit;
use App\SiteChecker;
use PhpAmqpLib\Message\AMQPMessage;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$rabbit  = new Rabbit($config);
$checker = new SiteChecker($config['checker']['timeout']);

echo "Worker aguardando mensagens na fila '{$config['queue']}'. CTRL+C para sair.\n";

$rabbit->consume(function (AMQPMessage $msg) use ($checker, $config) {
    $data = json_decode($msg->getBody(), true);

    if (!is_array($data) || empty($data['url'])) {
        fwrite(STDERR, "Mensagem inválida descartada: {$msg->getBody()}\n");
        $msg->reject(false);
        return;
    }

    try {
        $result = $checker->check($data['url']);

        $written = file_put_contents(
            $config['log_file'],
            json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($written === false) {
            throw new RuntimeException("Não foi possível gravar em {$config['log_file']}");
        }

        printf(
            "[%s] %s -> %d (%d ms)\n",
            $result['ok'] ? 'OK' : 'FALHA',
            $result['url'],
            $result['status'],
            $result['time_ms']
        );

        $msg->ack();
    } catch (Throwable $e) {
        fwrite(STDERR, "Erro ao processar {$data['url']}: {$e->getMessage()}\n");
        $msg->nack(!$msg->isRedelivered());
    }
});
