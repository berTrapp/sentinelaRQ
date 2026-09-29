# SentinelaQ

Monitor de sites com fila no RabbitMQ. Um produtor enfileira URLs e um ou mais workers checam cada site (status HTTP e tempo de resposta), gravando o resultado em log.

```
enqueue_check.php  ──►  [ fila site_checks ]  ──►  check_worker.php  ──►  storage/logs.log
```

## Stack

- PHP 8.3 (CLI) + [php-amqplib](https://github.com/php-amqplib/php-amqplib)
- RabbitMQ 3.13 com painel de gerenciamento
- Docker Compose

## Como rodar

```bash
docker compose up -d --build
```

O container `php` roda o `composer install` ao subir. Acompanhe com `docker compose logs -f php`.

> As portas 5672 e 15672 não podem estar em uso por outro RabbitMQ (por exemplo, o do `projeto/`).

## Uso

**Iniciar um worker** (fica escutando a fila; `CTRL+C` para sair):

```bash
docker compose exec php php bin/check_worker.php
```

**Enfileirar URLs** (em outro terminal):

```bash
docker compose exec php php bin/enqueue_check.php https://github.com https://php.net
```

**Ver os resultados:**

```bash
tail -f storage/logs.log
```

Cada linha é um JSON:

```json
{"url":"https://github.com","status":200,"ok":true,"time_ms":312,"error":null,"checked_at":"2026-09-29 16:44:49"}
```

Para processar em paralelo, basta abrir mais workers em terminais separados. O prefetch = 1 faz o RabbitMQ distribuir as mensagens entre eles.

## Painel do RabbitMQ

http://localhost:15672 (usuário `admin`, senha `pass`). A fila fica em **Queues → site_checks**.

## Estrutura

```
bin/
  enqueue_check.php   produtor: valida e publica URLs na fila
  check_worker.php    consumidor: checa o site, grava o log e confirma (ack)
src/
  Rabbit.php          conexão, declaração da fila, publish e consume
  SiteChecker.php     requisição HTTP (HEAD via cURL) e montagem do resultado
config.php            configuração lida das variáveis de ambiente
storage/              logs gerados pelo worker
php/Dockerfile        imagem PHP com as extensões necessárias
```

## Configuração

As variáveis de conexão vêm do `docker-compose.yml` (`RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_USER`, `RABBITMQ_PASS`). Nome da fila, timeout da checagem e caminho do log ficam em `config.php`.

## Comportamento das mensagens

| Situação | Ação |
|---|---|
| Checagem concluída (mesmo com o site fora do ar) | `ack`: a mensagem sai da fila |
| Mensagem inválida (sem `url` ou com JSON quebrado) | `reject`: descartada |
| Erro no worker | `nack`: volta para a fila uma vez e, se falhar de novo, é descartada |

A fila é durável e as mensagens são persistentes, então sobrevivem a um restart do RabbitMQ.
